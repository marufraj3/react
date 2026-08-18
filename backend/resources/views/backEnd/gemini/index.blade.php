@extends('backEnd.layouts.master')

@section('title','Gemini Assistant')

@section('css')
<style>
:root {
    --gemini-purple: #5b49d6;
    --gemini-purple-dark: #4339c6;
    --gemini-bg: #f6f7fb;
}
.gemini-wrapper {
    max-width: 1050px;
    margin: 0 auto;
    padding: 15px;
}
.gemini-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 35%, #7c3aed 100%);
    border-radius: 16px;
    padding: 18px 22px;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    box-shadow: 0 10px 30px rgba(91,73,214,0.35);
}
.gemini-header::before {
    content: '';
    position: absolute;
    top: -40%; right: -20%;
    width: 60%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    pointer-events: none;
}
.gemini-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
}
.gemini-header-title {
    font-size: 20px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}
.gemini-header-title span.badge {
    background: rgba(255,255,255,0.2);
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 20px;
    font-weight: 600;
}
.gemini-header-subtitle {
    font-size: 12.5px;
    opacity: 0.9;
    margin-top: 6px;
    line-height: 1.5;
    max-width: 520px;
}
.gemini-header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
.gemini-btn {
    border: none;
    background: #fff;
    color: #4338ca;
    font-size: 12px;
    font-weight: 600;
    padding: 7px 12px;
    border-radius: 20px;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    text-decoration: none !important;
}
.gemini-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    color: #312c85;
}
.gemini-btn.secondary {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
}
.gemini-btn.secondary:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
}
.gemini-btn.danger {
    background: #ff4d6d;
    color: #fff;
}
.gemini-btn i {
    font-size: 12px;
}

/* Chat Card */
.gemini-chat-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 6px 30px rgba(20,20,43,0.08);
    overflow: hidden;
    border: 1px solid #eef0f6;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 230px);
    min-height: 520px;
}
.gemini-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 22px;
    background: #fdfdff;
    scroll-behavior: smooth;
}
.gemini-empty {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}
.gemini-empty .robot {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #e0e7ff, #ddd6fe);
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 16px;
}
.gemini-empty h4 {
    font-weight: 800;
    color: #111827;
    font-size: 18px;
    margin-bottom: 6px;
}
.gemini-empty p {
    font-size: 13px;
    margin-bottom: 16px;
    color: #6b7280;
}
.gemini-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-top: 12px;
}
.gemini-suggest-btn {
    font-size: 11.5px;
    padding: 6px 12px;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    background: #f3f4ff;
    color: #4338ca;
    cursor: pointer;
    transition: .2s;
    font-weight: 500;
}
.gemini-suggest-btn:hover {
    background: #4338ca;
    color: #fff;
    border-color: #4338ca;
}

/* Messages */
.gemini-msg {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
    max-width: 92%;
}
.gemini-msg.user {
    margin-left: auto;
    flex-direction: row-reverse;
}
.gemini-msg-avatar {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    font-weight: 700;
}
.gemini-msg.user .gemini-msg-avatar {
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    color: #fff;
}
.gemini-msg.model .gemini-msg-avatar {
    background: #f3f4ff;
    border: 1px solid #e0e7ff;
}
.gemini-msg-bubble {
    padding: 12px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.6;
    position: relative;
    word-break: break-word;
    white-space: pre-wrap;
}
.gemini-msg.user .gemini-msg-bubble {
    background: linear-gradient(135deg, #4338ca, #6366f1);
    color: #fff;
    border-bottom-right-radius: 6px;
}
.gemini-msg.model .gemini-msg-bubble {
    background: #fff;
    border: 1px solid #eef0f6;
    color: #1f2937;
    border-bottom-left-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.gemini-msg-bubble p { margin: 0 0 8px 0; }
.gemini-msg-bubble p:last-child { margin-bottom: 0; }
.gemini-msg-bubble ul, .gemini-msg-bubble ol {
    margin: 6px 0 6px 18px;
    padding: 0;
}
.gemini-msg-bubble code {
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 12px;
}
.gemini-msg-bubble pre {
    background: #1f2937;
    color: #e5e7eb;
    padding: 12px;
    border-radius: 10px;
    overflow-x: auto;
    font-size: 12px;
    margin: 8px 0;
}
.gemini-msg-time {
    font-size: 10px;
    opacity: 0.65;
    margin-top: 6px;
    text-align: right;
}
.gemini-msg.user .gemini-msg-time { color: #c7d2fe; }
.gemini-msg.model .gemini-msg-time { color: #9ca3af; }

/* Typing indicator */
.gemini-typing {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    color: #6b7280;
    font-size: 12px;
}
.gemini-typing.dots span {
    width: 6px; height: 6px;
    background: #4338ca;
    border-radius: 50%;
    display: inline-block;
    animation: geminiBounce 1.4s infinite ease-in-out both;
}
.gemini-typing.dots span:nth-child(1){ animation-delay: -0.32s; }
.gemini-typing.dots span:nth-child(2){ animation-delay: -0.16s; }
@keyframes geminiBounce {
    0%,80%,100%{ transform: scale(0); }
    40%{ transform: scale(1); }
}

/* Input Area */
.gemini-chat-footer {
    padding: 12px;
    border-top: 1px solid #f0f0f5;
    background: #fff;
}
.gemini-input-wrap {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 8px 10px;
    transition: .2s;
}
.gemini-input-wrap:focus-within {
    border-color: #6366f1;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
}
.gemini-input {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    outline: none;
    font-size: 13.5px;
    line-height: 1.5;
    max-height: 120px;
    min-height: 22px;
    padding: 6px 4px;
}
.gemini-send-btn {
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    border: none;
    color: #fff;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s;
    flex-shrink: 0;
    font-size: 14px;
    font-weight: 600;
}
.gemini-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(67,56,202,0.35);
}
.gemini-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Floating action (optional) - bottom right button from screenshot */
.gemini-float {
    position: fixed;
    right: 22px;
    bottom: 22px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    display: none; /* hidden on this page, but show if wanted elsewhere */
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 10px 25px rgba(99,102,241,0.4);
    z-index: 1050;
    cursor: pointer;
}

/* API Status warning */
.gemini-warning {
    background: #fff3cd;
    border: 1px solid #ffe49c;
    color: #856404;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
</style>
@endsection

@section('content')
<div class="gemini-wrapper">
    <!-- Header -->
    <div class="gemini-header">
        <div class="gemini-header-top">
            <div>
                <div class="gemini-header-title">
                    <span>✨</span> Gemini Advance Assistant <span class="badge">AI Powered</span>
                </div>
                <div class="gemini-header-subtitle">
                    এডমিন সাইট, ডাটাবেস, অর্ডার, প্রোডাক্ট ও অ্যাডমিন প্যানেল সম্পর্কে যেকোনো প্রশ্ন করুন।<br>
                    বাংলা বা ইংরেজিতে কথা বলুন।
                </div>
            </div>
            <div class="gemini-header-actions">
                <button class="gemini-btn" id="btnRefreshData"><i class="fe-refresh-cw"></i> Refresh Data</button>
                <button class="gemini-btn secondary" id="btnClearChat"><i class="fe-trash-2"></i> Clear Chat</button>
                <a href="{{ route('admin.gemini.settings') }}" class="gemini-btn"><i class="fe-settings"></i> API Settings</a>
            </div>
        </div>
    </div>

    @if(!$setting->isConfigured())
    <div class="gemini-warning">
        <div><i class="fe-alert-triangle"></i> Gemini API Key সেট করা নেই। দয়া করে সেটিংস এ গিয়ে API Key যোগ করুন।</div>
        <a href="{{ route('admin.gemini.settings') }}" class="gemini-btn" style="padding:5px 10px;font-size:11px;">Configure Now</a>
    </div>
    @endif

    <!-- Chat Card -->
    <div class="gemini-chat-card" id="chatCard">
        <div class="gemini-chat-body" id="chatBody">
            @if($chats->isEmpty())
                <div class="gemini-empty" id="emptyState">
                    <div class="robot">🤖</div>
                    <h4>আজ কী সাহায্য লাগবে?</h4>
                    <p>আমি আপনার স্টোরের ডাটা ও অ্যাডমিন সিস্টেম জানি।</p>
                    <div class="gemini-suggestions">
                        <button class="gemini-suggest-btn" data-prompt="আজ কত অর্ডার?">আজ কত অর্ডার?</button>
                        <button class="gemini-suggest-btn" data-prompt="Product approve কিভাবে করব?">Product approve</button>
                        <button class="gemini-suggest-btn" data-prompt="Fraud check কীভাবে কাজ করে?">Fraud check</button>
                        <button class="gemini-suggest-btn" data-prompt="Gemini API setup কিভাবে করব? ডকুমেন্টেশন দাও।">Gemini API setup</button>
                        <button class="gemini-suggest-btn" data-prompt="Pending orders গুলো কিভাবে ম্যানেজ করব?">Pending orders</button>
                        <button class="gemini-suggest-btn" data-prompt="আজকের রেভিনিউ কত?">আজকের রেভিনিউ</button>
                    </div>
                </div>
            @else
                @foreach($chats as $chat)
                    <div class="gemini-msg {{ $chat->role === 'user' ? 'user' : 'model' }}">
                        <div class="gemini-msg-avatar">
                            @if($chat->role === 'user')
                                {{ strtoupper(substr(Auth::guard('admin')->user()->name,0,1)) }}
                            @else
                                🤖
                            @endif
                        </div>
                        <div class="gemini-msg-bubble">
                            <div class="msg-text">{!! nl2br(e($chat->message)) !!}</div>
                            <div class="gemini-msg-time">{{ $chat->created_at->format('h:i A') }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="gemini-msg model" id="typingIndicator" style="display:none;">
                <div class="gemini-msg-avatar">🤖</div>
                <div class="gemini-msg-bubble">
                    <div class="gemini-typing dots" id="typingDots" style="display:flex;">
                        <span></span><span></span><span></span>
                        <span style="margin-left:8px;font-size:11px;color:#6b7280;">Gemini লিখছে...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="gemini-chat-footer">
            <div class="gemini-input-wrap">
                <textarea id="chatInput" class="gemini-input" rows="1" placeholder="মেসেজ লিখুন... (Enter = পাঠান, Shift+Enter = নতুন লাইন)"></textarea>
                <button id="sendBtn" class="gemini-send-btn" title="পাঠান (Enter)">
                    <i class="fe-send"></i>
                </button>
            </div>
            <div style="font-size:10px;color:#9ca3af;margin-top:6px;display:flex;justify-content:space-between;">
                <span>Model: {{ $setting->model }} @if($setting->status) • <span style="color:#10b981;">● Active</span> @else • <span style="color:#ef4444;">● Inactive</span> @endif</span>
                <span id="charCount">0 chars</span>
            </div>
        </div>
    </div>
</div>

<div class="gemini-float" id="floatBtn">🤖</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
$(function(){
    const $chatBody = $('#chatBody');
    const $chatInput = $('#chatInput');
    const $sendBtn = $('#sendBtn');
    const $typing = $('#typingIndicator');
    const $empty = $('#emptyState');
    const sessionId = "{{ $sessionId }}";

    function scrollToBottom(){
        $chatBody.stop().animate({ scrollTop: $chatBody[0].scrollHeight }, 300);
    }

    // Auto resize textarea
    $chatInput.on('input', function(){
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        $('#charCount').text(this.value.length + ' chars');
    });

    // Quick suggestion buttons
    $(document).on('click', '.gemini-suggest-btn', function(){
        const prompt = $(this).data('prompt');
        $chatInput.val(prompt).trigger('input').focus();
        sendMessage();
    });

    function escapeHtml(text){
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function formatAI(text){
        try {
            // Use marked for markdown, fallback to simple formatting
            let html = marked.parse(text);
            return html;
        } catch(e){
            return '<p>' + escapeHtml(text).replace(/\n/g,'<br>') + '</p>';
        }
    }

    function appendMessage(role, content, time){
        $empty.hide();
        const isUser = role === 'user';
        const avatar = isUser ? "{{ strtoupper(substr(Auth::guard('admin')->user()->name,0,1)) }}" : "🤖";
        const nowTime = time || new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        const bubbleContent = isUser ? `<div class="msg-text">${escapeHtml(content).replace(/\n/g,'<br>')}</div>` : `<div class="msg-text">${formatAI(content)}</div>`;

        const $msg = $(`
            <div class="gemini-msg ${isUser ? 'user' : 'model'}">
                <div class="gemini-msg-avatar">${avatar}</div>
                <div class="gemini-msg-bubble">
                    ${bubbleContent}
                    <div class="gemini-msg-time">${nowTime}</div>
                </div>
            </div>
        `);
        // Insert before typing indicator
        $typing.before($msg);
        scrollToBottom();
        if(typeof feather !== 'undefined') feather.replace();
    }

    function sendMessage(){
        const message = $chatInput.val().trim();
        if(!message) return;

        if(message.length > 4000){
            toastr.error('Message too long. Max 4000 characters.');
            return;
        }

        // Append user message instantly
        appendMessage('user', message);
        $chatInput.val('').css('height','auto').trigger('input');
        $chatInput.focus();

        // Show typing
        $typing.show();
        $sendBtn.prop('disabled', true);
        scrollToBottom();

        $.ajax({
            url: "{{ route('admin.gemini.chat') }}",
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Gemini-Session': sessionId
            },
            data: { message: message },
            success: function(res){
                $typing.hide();
                $sendBtn.prop('disabled', false);
                if(res.success){
                    appendMessage('model', res.message);
                } else {
                    appendMessage('model', '❌ Error: ' + (res.message || 'Unknown'));
                }
            },
            error: function(xhr){
                $typing.hide();
                $sendBtn.prop('disabled', false);
                let msg = 'Failed to get response';
                try {
                    const json = xhr.responseJSON;
                    if(json && json.message) msg = json.message;
                    if(json && json.error === 'no_api_key'){
                        appendMessage('model', `⚠️ ${msg} [API Settings]({{ route('admin.gemini.settings') }})`);
                        return;
                    }
                } catch(e){}
                appendMessage('model', '❌ ' + msg + ' (Status: '+xhr.status+')');
                if(xhr.status === 500){
                    console.error(xhr.responseText);
                }
            }
        });
    }

    $sendBtn.on('click', sendMessage);

    $chatInput.on('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey){
            e.preventDefault();
            sendMessage();
        }
    });

    // Clear Chat
    $('#btnClearChat').on('click', function(){
        if(!confirm('Are you sure to clear all chat?')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fe-loader fe-spin"></i> Clearing...');
        $.ajax({
            url: "{{ route('admin.gemini.clear') }}",
            method: "POST",
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Gemini-Session': sessionId },
            success: function(res){
                if(res.success){
                    // Remove all messages except empty and typing
                    $('.gemini-msg').not('#typingIndicator').remove();
                    $empty.show();
                    toastr.success('Chat cleared');
                    if(res.new_session_id){
                        // reload to get new session
                        // location.reload();
                    }
                }
            },
            error: function(){
                toastr.error('Failed to clear chat');
            },
            complete: function(){
                $btn.prop('disabled', false).html('<i class="fe-trash-2"></i> Clear Chat');
            }
        });
    });

    // Refresh Data
    $('#btnRefreshData').on('click', function(){
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fe-loader fe-spin"></i> Refreshing...');
        $.ajax({
            url: "{{ route('admin.gemini.refresh') }}",
            method: "POST",
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res){
                toastr.success('Live store data refreshed! AI will now use updated data.');
            },
            error: function(){
                toastr.error('Refresh failed');
            },
            complete: function(){
                $btn.prop('disabled', false).html('<i class="fe-refresh-cw"></i> Refresh Data');
            }
        });
    });

    // Initial scroll
    scrollToBottom();
    $chatInput.focus();

    // Support rendering of initial messages with markdown if needed
    $('.gemini-msg.model .msg-text').each(function(){
        const raw = $(this).text();
        // If raw contains markdown-like, render
        // Keep safe
    });
});
</script>
@endsection
