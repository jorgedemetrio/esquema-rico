<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_system_esquemaricochat
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\System\EsquemaricoChat\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

/**
 * Plugin de sistema Esquema Rico Chat.
 *
 * Responsável por injetar o chat de IA flutuante e responder as requisições AJAX.
 */
final class EsquemaricoChat extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender'         => 'aposRenderizar',
            'onAjaxEsquemaricochat' => 'onAjaxEsquemaricochat',
        ];
    }

    /**
     * Injeta o widget flutuante e os assets de chat no frontend do site.
     */
    public function aposRenderizar(): void
    {
        // Só atua no frontend (site).
        if ($this->app->isClient('administrator')) {
            return;
        }

        // Não injeta em feeds rss/atom ou requisições AJAX diretas.
        $format = $this->app->getInput()->get('format', 'html');
        if ($format !== 'html') {
            return;
        }

        $markup = $this->montarChatMarkup();

        $buffer = $this->app->getBody();
        if (str_contains($buffer, '</body>')) {
            $buffer = str_replace('</body>', $markup . '</body>', $buffer);
        } else {
            $buffer .= $markup;
        }
        $this->app->setBody($buffer);
    }

    /**
     * Ponto de entrada AJAX via com_ajax.
     *
     * Chamado por index.php?option=com_ajax&plugin=esquemaricochat&group=system&format=json
     */
    public function onAjaxEsquemaricochat(): array
    {
        // 1. Validar token CSRF.
        if (!$this->app->checkToken('post')) {
            return [
                'success' => false,
                'error'   => Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_CSRF')
            ];
        }

        // 2. Obter mensagem do usuário.
        $userMsg = trim($this->app->getInput()->getString('message', ''));
        if ($userMsg === '') {
            return [
                'success' => false,
                'error'   => Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_EMPTY_MSG')
            ];
        }

        // 3. Obter configurações do plugin.
        $provider = $this->params->get('provider', 'gemini');
        $token    = trim($this->params->get('token', ''));

        if ($token === '') {
            return [
                'success' => false,
                'error'   => Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_NOT_CONFIGURED')
            ];
        }

        // 4. Carregar nome e descrição do site.
        $siteConfig = $this->app->getConfig();
        $siteName   = $siteConfig->get('sitename', '');
        $siteDesc   = $siteConfig->get('MetaDesc', '');

        // 5. Carregar contexto (texto direto ou arquivo).
        $contextSource = $this->params->get('context_source', 'text');
        $contextText   = '';

        if ($contextSource === 'text') {
            $contextText = $this->params->get('context_text', '');
        } else {
            $filePath = $this->params->get('context_file_path', 'images/context.txt');
            $fullPath = JPATH_SITE . '/' . ltrim($filePath, '/\\');

            if (file_exists($fullPath)) {
                $contextText = file_get_contents($fullPath);
            } else {
                return [
                    'success' => false,
                    'error'   => Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_CONTEXT_FILE_NOT_FOUND')
                ];
            }
        }

        // 6. Verificar identidade do usuário.
        $user       = $this->app->getIdentity();
        $isLoggedIn = $user && !$user->guest;
        $userName   = $isLoggedIn ? $user->name : '';

        // 7. Carregar histórico do banco se estiver logado.
        $history = [];
        if ($isLoggedIn) {
            $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__esquemarico_chat_history'))
                ->where($db->quoteName('user_id') . ' = :user_id')
                ->bind(':user_id', $user->id, ParameterType::INTEGER)
                ->order($db->quoteName('id') . ' ASC');
            try {
                $db->setQuery($query, 0, 10); // Carrega as últimas 10 mensagens para o prompt.
                $history = $db->loadObjectList() ?: [];
            } catch (\Throwable) {
                $history = [];
            }
        }

        // 8. Construir prompt do sistema.
        $assistantName = $this->params->get('assistant_name', 'Assistente de IA');
        $systemPrompt  = "Você é um assistente virtual chamado " . $assistantName . " para o site " . $siteName;
        if ($siteDesc !== '') {
            $systemPrompt .= " (" . $siteDesc . ")";
        }
        $systemPrompt .= ".\nO contexto de informações sobre o site é o seguinte:\n" . $contextText . "\n\n";
        $systemPrompt .= "Instruções:\n";
        $systemPrompt .= "1. Responda às dúvidas do usuário sobre o site de forma prestativa, educada, amigável e concisa com base neste contexto.\n";
        $systemPrompt .= "2. Se a informação não estiver presente no contexto fornecido, educadamente diga que não tem essa informação no momento.\n";
        
        if ($isLoggedIn && $userName !== '') {
            $systemPrompt .= "3. O usuário atual com quem você está conversando se chama " . $userName . ". Dirija-se a ele/ela pelo nome quando apropriado para uma conversa mais próxima.\n";
        }

        // 9. Disparar chamada de API.
        $aiResponse = match ($provider) {
            'gemini'   => $this->chamarGemini($token, $systemPrompt, $history, $userMsg),
            'chatgpt'  => $this->chamarChatGPT($token, $systemPrompt, $history, $userMsg),
            'claude'   => $this->chamarClaude($token, $systemPrompt, $history, $userMsg),
            'deepseek' => $this->chamarDeepSeek($token, $systemPrompt, $history, $userMsg),
            default    => null,
        };

        if ($aiResponse === null) {
            return [
                'success' => false,
                'error'   => Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_API')
            ];
        }

        $aiResponse = trim($aiResponse);

        // 10. Persistir histórico no banco de dados se o usuário estiver logado.
        if ($isLoggedIn) {
            $db  = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $now = (new \DateTime('now', new \DateTimeZone($this->app->get('offset', 'UTC'))))->format('Y-m-d H:i:s');

            try {
                // Mensagem do Usuário
                $senderUser = 'user';
                $queryUser = $db->getQuery(true)
                    ->insert($db->quoteName('#__esquemarico_chat_history'))
                    ->columns([
                        $db->quoteName('user_id'),
                        $db->quoteName('sender'),
                        $db->quoteName('message'),
                        $db->quoteName('created_at')
                    ])
                    ->values(':user_id, :sender, :message, :created_at')
                    ->bind(':user_id', $user->id, ParameterType::INTEGER)
                    ->bind(':sender', $senderUser)
                    ->bind(':message', $userMsg)
                    ->bind(':created_at', $now);

                $db->setQuery($queryUser)->execute();

                // Resposta da IA
                $senderAi = 'ai';
                $queryAi = $db->getQuery(true)
                    ->insert($db->quoteName('#__esquemarico_chat_history'))
                    ->columns([
                        $db->quoteName('user_id'),
                        $db->quoteName('sender'),
                        $db->quoteName('message'),
                        $db->quoteName('created_at')
                    ])
                    ->values(':user_id, :sender, :message, :created_at')
                    ->bind(':user_id', $user->id, ParameterType::INTEGER)
                    ->bind(':sender', $senderAi)
                    ->bind(':message', $aiResponse)
                    ->bind(':created_at', $now);

                $db->setQuery($queryAi)->execute();
            } catch (\Throwable) {
                // Não interrompe a resposta do chat por erro de persistência.
            }
        }

        return [
            'success'  => true,
            'response' => $aiResponse
        ];
    }

    /**
     * Monta o código HTML/CSS/JS do chat flutuante.
     */
    private function montarChatMarkup(): string
    {
        $assistantName  = htmlspecialchars($this->params->get('assistant_name', 'Assistente de IA'), ENT_QUOTES, 'UTF-8');
        $assistantImage = $this->params->get('assistant_image', '');

        // Avatar
        if ($assistantImage !== '') {
            $avatarHtml = '<img src="' . Uri::base() . htmlspecialchars($assistantImage, ENT_QUOTES, 'UTF-8') . '" alt="' . $assistantName . '" class="esr-chat-avatar" />';
        } else {
            // SVG de robô default
            $avatarHtml = '<div class="esr-chat-avatar"><svg viewBox="0 0 24 24"><path d="M19 8h-2V6c0-1.1-.9-2-2-2h-3V2h-2v2H7c-1.1 0-2 .9-2 2v2H3c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h2v2c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-2h2c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2zM7 6h8v2H7V6zm12 12H5v-6h14v6zm-11.5-2c-.83 0-1.5-.67-1.5-1.5S6.67 13 7.5 13s1.5.67 1.5 1.5S8.33 16 7.5 16zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg></div>';
        }

        // Usuário logado
        $user       = $this->app->getIdentity();
        $isLoggedIn = $user && !$user->guest;
        $userName   = $isLoggedIn ? $user->name : '';

        // Boas-vindas
        if ($isLoggedIn && $userName !== '') {
            $welcomeText = sprintf(Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_HELLO_USER'), $userName);
        } else {
            $welcomeText = Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_HELLO_GUEST');
        }

        // Histórico
        $historyList = [];
        if ($isLoggedIn) {
            $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__esquemarico_chat_history'))
                ->where($db->quoteName('user_id') . ' = :user_id')
                ->bind(':user_id', $user->id, ParameterType::INTEGER)
                ->order($db->quoteName('id') . ' ASC');
            try {
                $db->setQuery($query, 0, 50); // Carrega mais histórico para exibição inicial.
                $historyList = $db->loadAssocList() ?: [];
            } catch (\Throwable) {
                $historyList = [];
            }
        }
        $historyJson = json_encode($historyList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $csrfToken = Session::getFormToken();

        // Inicia markup HTML/CSS/JS
        $html = '
<!-- Esquema Rico Chat Widget -->
<div id="esr-chat-wrapper">
    <!-- Botão Flutuante (Launcher) -->
    <div class="esr-chat-launcher" onclick="esrToggleChat()" title="' . Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_CHAT_HEADER') . '">
        <svg viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/>
        </svg>
    </div>

    <!-- Janela de Chat -->
    <div class="esr-chat-container" id="esr-chat-container">
        <!-- Cabeçalho -->
        <div class="esr-chat-header">
            <div class="esr-chat-assistant-info">
                ' . $avatarHtml . '
                <div>
                    <div class="esr-chat-name">' . $assistantName . '</div>
                    <div class="esr-chat-status">
                        <span class="esr-chat-status-dot"></span> Online
                    </div>
                </div>
            </div>
            <button class="esr-chat-close" onclick="esrToggleChat()">
                <svg viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        <!-- Mensagens -->
        <div class="esr-chat-body" id="esr-chat-body">
            <div class="esr-chat-bubble esr-ai">' . htmlspecialchars($welcomeText, ENT_QUOTES, 'UTF-8') . '</div>
        </div>

        <!-- Indicador de Digitação -->
        <div class="esr-chat-typing" id="esr-chat-typing">
            <span class="esr-chat-typing-dot"></span>
            <span class="esr-chat-typing-dot"></span>
            <span class="esr-chat-typing-dot"></span>
        </div>

        <!-- Rodapé / Input -->
        <div class="esr-chat-footer">
            <input type="text" class="esr-chat-input" id="esr-chat-input" placeholder="' . Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_PLACEHOLDER') . '" onkeypress="esrCheckEnter(event)" />
            <button class="esr-chat-send-btn" onclick="esrSendMessage()">
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* CSS do Chat Flutuante */
.esr-chat-launcher {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 999999;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.esr-chat-launcher:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}
.esr-chat-launcher svg {
    width: 30px;
    height: 30px;
    fill: #ffffff;
}

.esr-chat-container {
    position: fixed;
    bottom: 95px;
    right: 25px;
    width: 380px;
    height: 520px;
    max-height: calc(100vh - 120px);
    max-width: calc(100vw - 50px);
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    z-index: 999999;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.esr-chat-container.esr-open {
    display: flex;
    opacity: 1;
    transform: translateY(0) scale(1);
}

.esr-chat-header {
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    color: #ffffff;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.esr-chat-assistant-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.esr-chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.5);
    overflow: hidden;
}
.esr-chat-avatar svg {
    width: 24px;
    height: 24px;
    fill: #ffffff;
}
.esr-chat-name {
    font-weight: 600;
    font-size: 16px;
    letter-spacing: 0.5px;
}
.esr-chat-status {
    font-size: 11px;
    opacity: 0.8;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.esr-chat-status-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 8px #10b981;
    animation: esr-pulse 2s infinite;
}
@keyframes esr-pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.esr-chat-close {
    cursor: pointer;
    background: transparent;
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.2s ease;
}
.esr-chat-close:hover {
    background: rgba(255, 255, 255, 0.1);
}
.esr-chat-close svg {
    width: 18px;
    height: 18px;
    fill: #ffffff;
}

.esr-chat-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: rgba(243, 244, 246, 0.7);
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}
.esr-chat-body::-webkit-scrollbar {
    width: 6px;
}
.esr-chat-body::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
}

.esr-chat-bubble {
    max-width: 80%;
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    animation: esr-fade-in 0.3s ease forwards;
}
@keyframes esr-fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.esr-chat-bubble.esr-user {
    align-self: flex-end;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
    border-bottom-right-radius: 4px;
}
.esr-chat-bubble.esr-ai {
    align-self: flex-start;
    background: #ffffff;
    color: #1f2937;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(229, 231, 235, 0.5);
}

.esr-chat-typing {
    align-self: flex-start;
    background: #ffffff;
    padding: 12px 16px;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(229, 231, 235, 0.5);
    display: none;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    margin: 0 20px 20px 20px;
    position: absolute;
    bottom: 60px;
    z-index: 10;
}
.esr-chat-typing-dot {
    width: 6px;
    height: 6px;
    background: #9ca3af;
    border-radius: 50%;
    animation: esr-typing 1.4s infinite ease-in-out both;
}
.esr-chat-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.esr-chat-typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes esr-typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.esr-chat-footer {
    padding: 15px;
    background: #ffffff;
    border-top: 1px solid rgba(229, 231, 235, 0.8);
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 12;
}
.esr-chat-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 24px;
    padding: 10px 18px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: #f9fafb;
}
.esr-chat-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    background: #ffffff;
}
.esr-chat-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    transition: all 0.2s ease;
}
.esr-chat-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}
.esr-chat-send-btn svg {
    width: 18px;
    height: 18px;
    fill: #ffffff;
    transform: translateX(1px);
}
</style>

<script>
// Lógica de execução do Chat Flutuante
const esrHistory = ' . $historyJson . ';
const esrTokenName = "' . $csrfToken . '";

document.addEventListener("DOMContentLoaded", function() {
    // Carregar histórico se existir
    if (esrHistory && esrHistory.length > 0) {
        const body = document.getElementById("esr-chat-body");
        esrHistory.forEach(function(item) {
            const bubble = document.createElement("div");
            bubble.classList.add("esr-chat-bubble");
            bubble.classList.add(item.sender === "user" ? "esr-user" : "esr-ai");
            bubble.innerText = item.message;
            body.appendChild(bubble);
        });
        setTimeout(esrScrollToBottom, 100);
    }
});

function esrToggleChat() {
    const container = document.getElementById("esr-chat-container");
    if (container.classList.contains("esr-open")) {
        container.style.opacity = "0";
        container.style.transform = "translateY(20px) scale(0.95)";
        setTimeout(function() {
            container.classList.remove("esr-open");
        }, 300);
    } else {
        container.classList.add("esr-open");
        setTimeout(function() {
            container.style.opacity = "1";
            container.style.transform = "translateY(0) scale(1)";
        }, 10);
        setTimeout(esrScrollToBottom, 50);
        document.getElementById("esr-chat-input").focus();
    }
}

function esrScrollToBottom() {
    const body = document.getElementById("esr-chat-body");
    body.scrollTop = body.scrollHeight;
}

function esrCheckEnter(e) {
    if (e.key === "Enter") {
        esrSendMessage();
    }
}

function esrSendMessage() {
    const input = document.getElementById("esr-chat-input");
    const msg = input.value.trim();
    if (msg === "") return;

    // Append mensagem do usuário
    const body = document.getElementById("esr-chat-body");
    const userBubble = document.createElement("div");
    userBubble.classList.add("esr-chat-bubble", "esr-user");
    userBubble.innerText = msg;
    body.appendChild(userBubble);
    
    input.value = "";
    esrScrollToBottom();

    // Exibir digitando
    const typing = document.getElementById("esr-chat-typing");
    typing.style.display = "flex";

    // Preparar dados
    const params = new URLSearchParams();
    params.append("message", msg);
    params.append(esrTokenName, "1");

    // Requisição AJAX
    fetch("' . Uri::base() . 'index.php?option=com_ajax&plugin=esquemaricochat&group=system&format=json", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        typing.style.display = "none";
        if (data.success && data.data && data.data[0]) {
            const pluginResult = typeof data.data[0] === "string" ? JSON.parse(data.data[0]) : data.data[0];
            if (pluginResult.success) {
                const aiBubble = document.createElement("div");
                aiBubble.classList.add("esr-chat-bubble", "esr-ai");
                aiBubble.innerText = pluginResult.response;
                body.appendChild(aiBubble);
            } else {
                esrAppendError(pluginResult.error || "' . Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_API') . '");
            }
        } else {
            esrAppendError("' . Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_API') . '");
        }
        esrScrollToBottom();
    })
    .catch(function(err) {
        typing.style.display = "none";
        esrAppendError("' . Text::_('PLG_SYSTEM_ESQUEMARICOCHAT_ERROR_API') . '");
        esrScrollToBottom();
    });
}

function esrAppendError(text) {
    const body = document.getElementById("esr-chat-body");
    const errorBubble = document.createElement("div");
    errorBubble.classList.add("esr-chat-bubble", "esr-ai");
    errorBubble.style.color = "#dc2626";
    errorBubble.style.border = "1px solid #fca5a5";
    errorBubble.style.background = "#fee2e2";
    errorBubble.innerText = text;
    body.appendChild(errorBubble);
}
</script>
';
        return $html;
    }

    /**
     * Auxiliar genérico para requisição cURL.
     */
    private function chamarApi(string $url, string $method, array $headers, array $payload): ?string
    {
        if (!\function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        return $response;
    }

    /**
     * Integração com a API Google Gemini.
     */
    private function chamarGemini(string $token, string $systemPrompt, array $history, string $userMsg): ?string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($token);

        $contents = [];
        foreach ($history as $h) {
            $contents[] = [
                'role'  => $h->sender === 'user' ? 'user' : 'model',
                'parts' => [['text' => $h->message]]
            ];
        }
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMsg]]
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents'          => $contents
        ];

        $response = $this->chamarApi($url, 'POST', ['Content-Type: application/json'], $payload);

        if ($response !== null) {
            $data = json_decode($response, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
        }

        return null;
    }

    /**
     * Integração com a API OpenAI ChatGPT.
     */
    private function chamarChatGPT(string $token, string $systemPrompt, array $history, string $userMsg): ?string
    {
        $url = 'https://api.openai.com/v1/chat/completions';

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        foreach ($history as $h) {
            $messages[] = [
                'role'    => $h->sender === 'user' ? 'user' : 'assistant',
                'content' => $h->message
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $userMsg
        ];

        $payload = [
            'model'    => 'gpt-4o-mini',
            'messages' => $messages
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $response = $this->chamarApi($url, 'POST', $headers, $payload);

        if ($response !== null) {
            $data = json_decode($response, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
        }

        return null;
    }

    /**
     * Integração com a API Anthropic Claude.
     */
    private function chamarClaude(string $token, string $systemPrompt, array $history, string $userMsg): ?string
    {
        $url = 'https://api.anthropic.com/v1/messages';

        $messages = [];
        foreach ($history as $h) {
            $messages[] = [
                'role'    => $h->sender === 'user' ? 'user' : 'assistant',
                'content' => $h->message
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $userMsg
        ];

        $payload = [
            'model'      => 'claude-3-5-haiku-20241022',
            'max_tokens' => 1024,
            'system'     => $systemPrompt,
            'messages'   => $messages
        ];

        $headers = [
            'content-type: application/json',
            'x-api-key: ' . $token,
            'anthropic-version: 2023-06-01'
        ];

        $response = $this->chamarApi($url, 'POST', $headers, $payload);

        if ($response !== null) {
            $data = json_decode($response, true);
            if (isset($data['content'][0]['text'])) {
                return $data['content'][0]['text'];
            }
        }

        return null;
    }

    /**
     * Integração com a API DeepSeek.
     */
    private function chamarDeepSeek(string $token, string $systemPrompt, array $history, string $userMsg): ?string
    {
        $url = 'https://api.deepseek.com/chat/completions';

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        foreach ($history as $h) {
            $messages[] = [
                'role'    => $h->sender === 'user' ? 'user' : 'assistant',
                'content' => $h->message
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $userMsg
        ];

        $payload = [
            'model'    => 'deepseek-chat',
            'messages' => $messages
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $response = $this->chamarApi($url, 'POST', $headers, $payload);

        if ($response !== null) {
            $data = json_decode($response, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
        }

        return null;
    }
}
