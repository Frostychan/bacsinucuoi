@php($chatbotInitiallyOpen = request()->routeIs('chatbot'))

<div class="chatbot-widget" data-chatbot-widget data-start-open="{{ $chatbotInitiallyOpen ? 'true' : 'false' }}">
    <button
        class="chatbot-toggle"
        type="button"
        data-chatbot-toggle
        aria-controls="chatbot-panel"
        aria-expanded="{{ $chatbotInitiallyOpen ? 'true' : 'false' }}"
        aria-label="{{ $chatbotInitiallyOpen ? 'Đóng trợ lý AI' : 'Mở trợ lý AI' }}"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 10h10M7 14h6m-1 7-3.5-3H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-4l-3 3Z" />
        </svg>
    </button>

    <section
        class="chatbot-panel"
        id="chatbot-panel"
        role="dialog"
        aria-labelledby="chatbot-title"
        @if (! $chatbotInitiallyOpen) hidden @endif
    >
        <header class="chatbot-header">
            <div class="chatbot-heading">
                <span class="chatbot-avatar" aria-hidden="true">✦</span>
                <div>
                    <h2 id="chatbot-title">Trợ lý AI MedCare</h2>
                    <p>Hỗ trợ đặt lịch khám</p>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button class="chatbot-header-button" type="button" data-chatbot-reset aria-label="Bắt đầu cuộc trò chuyện mới" title="Cuộc trò chuyện mới">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 2.64-6.36L3 8m0-5v5h5" />
                    </svg>
                </button>
                <button class="chatbot-header-button" type="button" data-chatbot-close aria-label="Đóng trợ lý AI">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </header>

        <div class="chatbot-messages" data-chatbot-messages role="log" aria-live="polite" aria-relevant="additions text">
            <div class="chatbot-message chatbot-message-assistant">Xin chào! Bạn có thể mô tả triệu chứng để mình gợi ý bác sĩ phù hợp, hoặc <a class="chatbot-inline-link" href="{{ route('catalog.doctors') }}">xem danh sách bác sĩ</a>.</div>
        </div>

        <form
            class="chatbot-form"
            data-chatbot-form
            data-send-url="{{ route('chatbot.send') }}"
            data-reset-url="{{ route('chatbot.reset') }}"
        >
            <label class="sr-only" for="chatbot-input">Nhập câu hỏi</label>
            <input
                class="chatbot-input"
                id="chatbot-input"
                data-chatbot-input
                type="text"
                name="message"
                placeholder="Nhập câu hỏi..."
                maxlength="1000"
                autocomplete="off"
                required
            >
            <button class="chatbot-send" type="submit" data-chatbot-send aria-label="Gửi tin nhắn">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" />
                </svg>
            </button>
        </form>
        <p class="chatbot-privacy-note">Nội dung chat được gửi đến AI để trả lời. Vui lòng không nhập thông tin định danh hoặc hồ sơ bệnh án chi tiết.</p>
    </section>
</div>

@once
    <style>
        .chatbot-widget,
        .chatbot-widget * {
            box-sizing: border-box;
        }

        .chatbot-toggle {
            position: fixed;
            z-index: 1000;
            bottom: 20px;
            right: 20px;
            display: grid;
            width: 56px;
            height: 56px;
            place-items: center;
            border: 0;
            border-radius: 50%;
            color: #fff;
            background: #087e8b;
            box-shadow: 0 8px 24px rgba(7, 59, 76, .28);
            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }

        .chatbot-toggle:hover {
            background: #05606c;
            transform: translateY(-2px);
        }

        .chatbot-toggle:focus-visible,
        .chatbot-header-button:focus-visible,
        .chatbot-send:focus-visible,
        .chatbot-input:focus-visible {
            outline: 3px solid #66d8d4;
            outline-offset: 3px;
        }

        .chatbot-toggle svg {
            width: 25px;
            height: 25px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .chatbot-panel {
            position: fixed;
            z-index: 1001;
            bottom: 88px;
            right: 20px;
            display: flex;
            width: min(370px, calc(100vw - 40px));
            height: min(520px, calc(100dvh - 112px));
            min-height: 320px;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #dceaf1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 55px rgba(7, 59, 76, .2);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .chatbot-panel[hidden] {
            display: none;
        }

        .chatbot-header {
            display: flex;
            min-height: 70px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 15px;
            color: #fff;
            background: linear-gradient(110deg, #063746, #087e8b);
        }

        .chatbot-heading,
        .chatbot-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chatbot-avatar {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            place-items: center;
            border-radius: 50%;
            color: #075365;
            background: #dff7f7;
            font-size: 19px;
        }

        .chatbot-heading h2 {
            margin: 0;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }

        .chatbot-heading p {
            margin: 3px 0 0;
            color: #c4e5e5;
            font-size: 11px;
        }

        .chatbot-header-actions {
            gap: 4px;
        }

        .chatbot-header-button {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 0;
            border-radius: 9px;
            color: #fff;
            background: transparent;
            cursor: pointer;
        }

        .chatbot-header-button:hover {
            background: rgba(255, 255, 255, .14);
        }

        .chatbot-header-button svg,
        .chatbot-send svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .chatbot-messages {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto;
            padding: 18px 14px;
            background: #f5f9fa;
        }

        .chatbot-message {
            max-width: 85%;
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.55;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .chatbot-message-assistant {
            align-self: flex-start;
            border: 1px solid #e4edf0;
            border-bottom-left-radius: 5px;
            color: #16324f;
            background: #fff;
        }

        .chatbot-message-user {
            align-self: flex-end;
            border-bottom-right-radius: 5px;
            color: #fff;
            background: #087e8b;
        }

        .chatbot-inline-link {
            color: #05606c;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .chatbot-inline-link:hover {
            color: #087e8b;
        }

        .chatbot-form {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 12px;
            border-top: 1px solid #e4edf0;
            background: #fff;
        }

        .chatbot-privacy-note {
            margin: -5px 0 0;
            padding: 0 12px 10px;
            color: #678096;
            background: #fff;
            font-size: 10px;
            line-height: 1.45;
        }

        .chatbot-input {
            width: 0;
            min-width: 0;
            flex: 1;
            padding: 11px 12px;
            border: 1px solid #dceaf1;
            border-radius: 10px;
            color: #16324f;
            background: #fff;
            font-size: 13px;
        }

        .chatbot-input:focus {
            border-color: #087e8b;
            outline: none;
        }

        .chatbot-send {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            place-items: center;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #087e8b;
            cursor: pointer;
        }

        .chatbot-send:hover:not(:disabled) {
            background: #05606c;
        }

        .chatbot-send:disabled {
            background: #8ebdc1;
            cursor: wait;
        }

        .chatbot-widget .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 480px) {
            .chatbot-toggle {
                bottom: 16px;
                right: 16px;
                width: 52px;
                height: 52px;
            }

            .chatbot-panel {
                bottom: 78px;
                right: 16px;
                width: calc(100vw - 32px);
                height: min(520px, calc(100dvh - 98px));
            }
        }
    </style>

    <script>
        (() => {
            const widget = document.querySelector('[data-chatbot-widget]');
            if (!widget) {
                return;
            }

            const toggle = widget.querySelector('[data-chatbot-toggle]');
            const panel = widget.querySelector('#chatbot-panel');
            const closeButton = widget.querySelector('[data-chatbot-close]');
            const resetButton = widget.querySelector('[data-chatbot-reset]');
            const messages = widget.querySelector('[data-chatbot-messages]');
            const form = widget.querySelector('[data-chatbot-form]');
            const input = widget.querySelector('[data-chatbot-input]');
            const sendButton = widget.querySelector('[data-chatbot-send]');

            const setOpen = (isOpen) => {
                panel.hidden = !isOpen;
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', isOpen ? 'Đóng trợ lý AI' : 'Mở trợ lý AI');
                if (isOpen) {
                    input.focus();
                }
            };

            const addMessage = (text, sender) => {
                const message = document.createElement('div');
                message.className = `chatbot-message chatbot-message-${sender}`;

                if (sender !== 'assistant') {
                    message.textContent = text;
                } else {
                    const bookingLinks = /\/appointments\/create(?:\?doctor_id=\d+)?|\/doctors|\/specialties|\/login/g;
                    const linkLabel = (path) => {
                        if (path.startsWith('/appointments/create?doctor_id=')) {
                            return 'Đặt lịch với bác sĩ';
                        }
                        if (path.startsWith('/appointments/create')) {
                            return 'Mở biểu mẫu đặt lịch';
                        }
                        if (path === '/doctors') {
                            return 'Xem danh sách bác sĩ';
                        }
                        if (path === '/specialties') {
                            return 'Xem danh sách chuyên khoa';
                        }

                        return 'Đăng nhập';
                    };
                    let cursor = 0;

                    for (const match of text.matchAll(bookingLinks)) {
                        message.append(document.createTextNode(text.slice(cursor, match.index)));

                        const link = document.createElement('a');
                        link.className = 'chatbot-inline-link';
                        link.href = match[0];
                        link.textContent = linkLabel(match[0]);
                        message.append(link);
                        cursor = match.index + match[0].length;
                    }

                    message.append(document.createTextNode(text.slice(cursor)));
                }

                messages.appendChild(message);
                messages.scrollTop = messages.scrollHeight;
                return message;
            };

            const postJson = async (url, body = {}) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(() => ({}));

                return { response, data };
            };

            toggle.addEventListener('click', () => setOpen(panel.hidden));
            closeButton.addEventListener('click', () => {
                setOpen(false);
                toggle.focus();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! panel.hidden) {
                    setOpen(false);
                    toggle.focus();
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const text = input.value.trim();
                if (!text || sendButton.disabled) {
                    return;
                }

                addMessage(text, 'user');
                input.value = '';
                input.disabled = true;
                sendButton.disabled = true;
                const waitingMessage = addMessage('Đang trả lời...', 'assistant');

                try {
                    const { response, data } = await postJson(form.dataset.sendUrl, { message: text });
                    if (response.status === 429) {
                        waitingMessage.textContent = 'Bạn gửi hơi nhanh, hãy đợi một chút rồi hỏi tiếp nhé.';
                    } else if (response.status === 419) {
                        waitingMessage.textContent = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
                    } else if (! response.ok) {
                        waitingMessage.textContent = data.reply || data.message || 'Trợ lý đang gặp sự cố. Vui lòng thử lại sau.';
                    } else {
                        waitingMessage.remove();
                        addMessage(data.reply || 'Bạn muốn khám vấn đề gì? Mình sẽ gợi ý bác sĩ đang nhận lịch phù hợp.', 'assistant');
                    }
                } catch (error) {
                    console.error('Không thể gửi tin nhắn tới trợ lý AI.', error);
                    waitingMessage.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
                } finally {
                    input.disabled = false;
                    sendButton.disabled = false;
                    input.focus();
                }
            });

            resetButton.addEventListener('click', async () => {
                resetButton.disabled = true;

                try {
                    const { response, data } = await postJson(form.dataset.resetUrl);
                    if (! response.ok) {
                        throw new Error(data.message || `Không thể bắt đầu cuộc trò chuyện mới (HTTP ${response.status}).`);
                    }

                    messages.replaceChildren();
                    addMessage('Xin chào! Bạn có thể mô tả triệu chứng hoặc chuyên khoa cần khám, mình sẽ gợi ý bác sĩ đang nhận lịch phù hợp.', 'assistant');
                    input.focus();
                } catch (error) {
                    console.error('Không thể đặt lại cuộc trò chuyện AI.', error);
                    addMessage('Không thể bắt đầu cuộc trò chuyện mới. Vui lòng thử lại.', 'assistant');
                } finally {
                    resetButton.disabled = false;
                }
            });

            setOpen(widget.dataset.startOpen === 'true');
        })();
    </script>
@endonce
