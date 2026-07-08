<?php
/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Registry\Registry;

/**
 * Controller que gerencia as interações AJAX com IAs para gerar matérias.
 */
class IaController extends BaseController
{
    /**
     * Gera uma matéria baseada no tema e/ou arquivo fornecido.
     *
     * @return void
     */
    public function gerar(): void
    {
        $this->checkToken('POST');

        $app   = Factory::getApplication();
        $input = $app->getInput();
        $tema  = $input->get('tema', '', 'raw');
        
        // Processa arquivo se houver
        $arquivoConteudo = '';
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['arquivo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
            if (\in_array($ext, ['txt', 'csv', 'md', 'json', 'xml'], true)) {
                $arquivoConteudo = file_get_contents($tmpPath);
            }
        }

        try {
            $config = $this->obterConfiguracoesIA();
            
            $prompt = $this->montarPromptGeracao($tema, $arquivoConteudo);
            $resposta = $this->chamarIA($config, $prompt);
            $dados = $this->parsearJsonIA($resposta);

            echo json_encode([
                'success' => true,
                'data'    => $dados
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        $app->close();
    }

    /**
     * Regera uma matéria com refinamento (feedback).
     *
     * @return void
     */
    public function regerar(): void
    {
        $this->checkToken('POST');

        $app         = Factory::getApplication();
        $input       = $app->getInput();
        $refinamento = $input->get('refinamento', '', 'raw');
        $contextoRaw = $input->get('contexto', '', 'raw');
        $contexto    = json_decode($contextoRaw, true) ?: [];

        try {
            $config = $this->obterConfiguracoesIA();
            
            $prompt = $this->montarPromptRefinamento($refinamento, $contexto);
            $resposta = $this->chamarIA($config, $prompt);
            $dados = $this->parsearJsonIA($resposta);

            echo json_encode([
                'success' => true,
                'data'    => $dados
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        $app->close();
    }

    /**
     * Obtém as configurações e chaves de API do plugin esquemaricoia.
     *
     * @return array
     * @throws \RuntimeException
     */
    private function obterConfiguracoesIA(): array
    {
        $plugin = PluginHelper::getPlugin('content', 'esquemaricoia');

        if (empty($plugin)) {
            throw new \RuntimeException('O plugin "Conteúdo - Esquema Rico: Gerador de Matérias via IA" não está habilitado ou instalado.');
        }

        $params = new Registry($plugin->params);
        $provider = $params->get('ia_provider', 'openai');

        $apiKey = match ($provider) {
            'gemini'  => $params->get('gemini_api_key', ''),
            'claude'  => $params->get('claude_api_key', ''),
            default   => $params->get('openai_api_key', ''),
        };

        if (empty($apiKey)) {
            throw new \RuntimeException('A chave de API para o provedor selecionado (' . strtoupper($provider) . ') não foi configurada nas opções do plugin.');
        }

        return [
            'provider' => $provider,
            'api_key'  => $apiKey
        ];
    }

    /**
     * Monta o prompt principal enviado à IA.
     *
     * @param string $tema
     * @param string $arquivo
     * @return string
     */
    private function montarPromptGeracao(string $tema, string $arquivo): string
    {
        $prompt = "Você é um redator sênior especialista em SEO (Search Engine Optimization) e Copywriting profissional em Português do Brasil.\n";
        $prompt .= "Sua tarefa é gerar uma matéria/artigo completo baseado no tema e/ou dados fornecidos abaixo:\n\n";
        
        if (!empty($tema)) {
            $prompt .= "TEMA / INSTRUÇÕES DO USUÁRIO:\n$tema\n\n";
        }
        
        if (!empty($arquivo)) {
            $prompt .= "DADOS EXTRAÍDOS DO ARQUIVO FORNECIDO:\n$arquivo\n\n";
        }

        $prompt .= "O artigo gerado deve seguir estritamente as melhores práticas de SEO moderno:\n";
        $prompt .= "1. TÍTULO: Deve ser altamente atrativo para o leitor e otimizado para motores de busca (máximo de 60 caracteres), idealmente contendo a palavra-chave principal no início.\n";
        $prompt .= "2. ALIAS: Deve ser uma URL amigável curta e limpa (slug) correspondente ao título, separada por hífens e apenas com caracteres alfanuméricos minúsculos.\n";
        $prompt .= "3. TEXTO: Formate o texto em HTML sem as tags estruturais (html, body, head), utilizando apenas tags de conteúdo como <p>, <h2>, <h3>, <strong>, <em>, <ul>, <li>.\n";
        $prompt .= "4. PARÁGRAFOS E SUBTÍTULOS: Escreva parágrafos curtos (máximo 150 palavras) e utilize pelo menos 3 subtítulos relevantes (H2/H3) bem distribuídos para estruturar o artigo.\n";
        $prompt .= "5. META DESCRIÇÃO: Crie uma meta descrição cativante que incentive a taxa de clique (CTR), com no máximo 155 caracteres.\n";
        $prompt .= "6. META PALAVRAS-CHAVE: Indique até 5 palavras-chave relevantes, separadas por vírgula.\n";
        $prompt .= "7. TAGS: Defina uma lista (array de strings) com até 5 tags curtas e altamente relevantes para organização interna no Joomla.\n\n";
        
        $prompt .= "Sua resposta deve ser EXCLUSIVAMENTE um objeto JSON válido em Português do Brasil com o seguinte formato, sem blocos de formatação markdown (como ```json) ou qualquer outro texto extra:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Título Otimizado do Artigo\",\n";
        $prompt .= "  \"alias\": \"slug-do-artigo\",\n";
        $prompt .= "  \"articletext\": \"<p>Texto do primeiro parágrafo...</p><h2>Subtítulo principal</h2><p>Texto do segundo...</p>\",\n";
        $prompt .= "  \"metadesc\": \"Meta descrição otimizada para SEO...\",\n";
        $prompt .= "  \"metakey\": \"palavra1, palavra2, palavra3\",\n";
        $prompt .= "  \"tags\": [\"Tag1\", \"Tag2\", \"Tag3\"]\n";
        $prompt .= "}";

        return $prompt;
    }

    /**
     * Monta o prompt de refinamento enviado à IA.
     *
     * @param string $refinamento
     * @param array $contexto
     * @return string
     */
    private function montarPromptRefinamento(string $refinamento, array $contexto): string
    {
        $prompt = "Você é um redator sênior especialista em SEO. O usuário gerou anteriormente o seguinte conteúdo:\n\n";
        $prompt .= "TÍTULO: " . ($contexto['title'] ?? '') . "\n";
        $prompt .= "TEXTO HTML: " . ($contexto['articletext'] ?? '') . "\n";
        $prompt .= "META DESCRIÇÃO: " . ($contexto['metadesc'] ?? '') . "\n\n";
        $prompt .= "O usuário solicitou as seguintes alterações ou melhorias específicas no conteúdo acima:\n";
        $prompt .= "\"$refinamento\"\n\n";
        $prompt .= "Por favor, regere o conteúdo aplicando as mudanças solicitadas, mantendo o foco em estratégias de SEO e devolvendo a resposta EXCLUSIVAMENTE no mesmo formato JSON válido, sem tags de markdown extra:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Título Otimizado do Artigo\",\n";
        $prompt .= "  \"alias\": \"slug-do-artigo\",\n";
        $prompt .= "  \"articletext\": \"Texto HTML modificado...\",\n";
        $prompt .= "  \"metadesc\": \"Meta descrição modificada...\",\n";
        $prompt .= "  \"metakey\": \"palavra1, palavra2\",\n";
        $prompt .= "  \"tags\": [\"Tag1\", \"Tag2\"]\n";
        $prompt .= "}";

        return $prompt;
    }

    /**
     * Faz a requisição HTTP para a API do provedor de IA correspondente.
     *
     * @param array $config
     * @param string $prompt
     * @return string
     * @throws \RuntimeException
     */
    private function chamarIA(array $config, string $prompt): string
    {
        $http = HttpFactory::getHttp();
        $provider = $config['provider'];
        $apiKey = $config['api_key'];

        if ($provider === 'gemini') {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
            $data = json_encode([
                'contents' => [[
                    'parts' => [['text' => $prompt]]
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);
            $headers = ['Content-Type' => 'application/json'];

            $res = $http->post($url, $data, $headers);

            if ($res->code !== 200) {
                throw new \RuntimeException('Erro na chamada da API Gemini. Código HTTP: ' . $res->code . ' | Resposta: ' . $res->body);
            }

            $resJson = json_decode($res->body, true);
            $text = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                throw new \RuntimeException('A API Gemini não retornou nenhum texto de resposta.');
            }

            return $text;
        }

        if ($provider === 'claude') {
            $url = 'https://api.anthropic.com/v1/messages';
            $data = json_encode([
                'model'      => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 4000,
                'messages'   => [['role' => 'user', 'content' => $prompt]]
            ]);
            $headers = [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json'
            ];

            $res = $http->post($url, $data, $headers);

            if ($res->code !== 200) {
                throw new \RuntimeException('Erro na chamada da API Claude. Código HTTP: ' . $res->code . ' | Resposta: ' . $res->body);
            }

            $resJson = json_decode($res->body, true);
            $text = $resJson['content'][0]['text'] ?? '';

            if (empty($text)) {
                throw new \RuntimeException('A API Claude não retornou nenhum texto de resposta.');
            }

            return $text;
        }

        // OpenAI ChatGPT
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = json_encode([
            'model'           => 'gpt-4o-mini',
            'messages'        => [
                ['role' => 'system', 'content' => 'Você é um gerador de conteúdo que responde estritamente em formato JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.7
        ]);
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json'
        ];

        $res = $http->post($url, $data, $headers);

        if ($res->code !== 200) {
            throw new \RuntimeException('Erro na chamada da API OpenAI. Código HTTP: ' . $res->code . ' | Resposta: ' . $res->body);
        }

        $resJson = json_decode($res->body, true);
        $text = $resJson['choices'][0]['message']['content'] ?? '';

        if (empty($text)) {
            throw new \RuntimeException('A API OpenAI não retornou nenhum texto de resposta.');
        }

        return $text;
    }

    /**
     * Limpa o markdown e parseia a string JSON retornada pela IA.
     *
     * @param string $resposta
     * @return array
     * @throws \RuntimeException
     */
    private function parsearJsonIA(string $resposta): array
    {
        $resposta = trim($resposta);

        // Remove blocos de código markdown se existirem (ex: ```json ... ```)
        if (preg_match('/```(?:json)?(.*?)```/is', $resposta, $matches)) {
            $jsonString = trim($matches[1]);
        } else {
            $jsonString = $resposta;
        }

        $dados = json_decode($jsonString, true);

        if (!\is_array($dados)) {
            throw new \RuntimeException('A resposta retornada pela IA não é um JSON válido. Resposta pura: ' . substr($resposta, 0, 300) . '...');
        }

        // Validação e higienização básica de chaves
        return [
            'title'       => trim((string) ($dados['title'] ?? '')),
            'alias'       => trim((string) ($dados['alias'] ?? '')),
            'articletext' => trim((string) ($dados['articletext'] ?? '')),
            'metadesc'    => trim((string) ($dados['metadesc'] ?? '')),
            'metakey'     => trim((string) ($dados['metakey'] ?? '')),
            'tags'        => (array) ($dados['tags'] ?? [])
        ];
    }
}
