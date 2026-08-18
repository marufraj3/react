@extends('backEnd.layouts.master')

@section('title','Gemini API Settings')

@section('css')
<style>
.gemini-settings-wrap { max-width: 900px; margin: 0 auto; padding: 20px; }
.gemini-settings-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #eef0f6;
    box-shadow: 0 8px 30px rgba(0,0,0,.06);
    overflow: hidden;
}
.gemini-settings-header {
    background: linear-gradient(135deg, #1e1b4b, #5b49d6);
    color: #fff;
    padding: 20px 24px;
}
.gemini-settings-header h4 { font-weight: 800; margin: 0; }
.gemini-settings-header p { opacity: .9; font-size: 12.5px; margin: 6px 0 0 0; }
.gemini-settings-body { padding: 24px; }
.gemini-form-group { margin-bottom: 18px; }
.gemini-form-group label { font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 6px; display:block; }
.gemini-form-control {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 13px;
    font-size: 13.5px;
    transition: .2s;
    background: #f9fafb;
}
.gemini-form-control:focus {
    outline: none;
    border-color: #6366f1;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(99,102,241,.12);
}
.gemini-help { font-size: 11px; color: #6b7280; margin-top: 4px; }
.gemini-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media(max-width:768px){ .gemini-row { grid-template-columns: 1fr; } }
.gemini-switch {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 500;
}
.gemini-switch input[type="checkbox"]{
    width: 44px; height: 24px;
    appearance: none;
    background: #e5e7eb;
    border-radius: 20px;
    position: relative;
    cursor: pointer;
    transition: .2s;
}
.gemini-switch input[type="checkbox"]:checked{ background: #6366f1; }
.gemini-switch input[type="checkbox"]::before{
    content:''; position:absolute;
    width:18px;height:18px;
    background:#fff; border-radius:50%;
    top:3px; left:3px;
    transition:.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.gemini-switch input[type="checkbox"]:checked::before{ transform: translateX(20px); }
.gemini-btn-primary{
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    color:#fff; border:none;
    padding: 11px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition:.2s;
}
.gemini-btn-primary:hover{ transform: translateY(-1px); box-shadow:0 8px 20px rgba(99,102,241,.35); }
.gemini-btn-secondary{
    background:#f3f4f6; color:#374151;
    border:1px solid #e5e7eb;
    padding: 10px 16px; border-radius:10px;
    font-weight:600; font-size:13px;
}
.gemini-model-badge{
    font-size:11px;
    background:#eef2ff;
    color:#4338ca;
    padding:3px 8px;
    border-radius:12px;
    font-weight:600;
}
.gemini-test-result{
    margin-top:12px;
    padding:12px;
    border-radius:10px;
    font-size:13px;
    display:none;
}
.gemini-test-result.success{ background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
.gemini-test-result.error{ background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
</style>
@endsection

@section('content')
<div class="gemini-settings-wrap">
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.gemini.index') }}" class="btn btn-outline-primary btn-sm"><i class="fe-arrow-left"></i> Back to Assistant</a>
        <span class="gemini-model-badge">{{ $setting->isConfigured() ? '● Configured & Active' : '○ Not Configured' }}</span>
    </div>

    <div class="gemini-settings-card">
        <div class="gemini-settings-header">
            <h4>⚙️ Gemini AI Settings</h4>
            <p>Configure your Google Gemini API key and model. Get API key from <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:#c7d2fe;text-decoration:underline;">Google AI Studio</a>. বাংলা / English দুই ভাষাই সাপোর্ট করে।</p>
        </div>
        <div class="gemini-settings-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.gemini.settings.update') }}">
                @csrf

                <div class="gemini-form-group">
                    <label>🔑 Gemini API Key *</label>
                    <input type="text" name="api_key" value="{{ old('api_key', $setting->api_key) }}" class="gemini-form-control" placeholder="AIzaSy... (from Google AI Studio)">
                    <div class="gemini-help">API Key গোপন রাখুন। <a href="https://aistudio.google.com/app/apikey" target="_blank">এখান থেকে API Key নিন</a>। Env তে GEMINI_API_KEY ও সেট করতে পারেন।</div>
                    @error('api_key') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="gemini-row">
                    <div class="gemini-form-group">
                        <label>🧠 Model</label>
                        <select name="model" class="gemini-form-control">
                            @php
                                $models = config('gemini.models', [
                                    'gemini-2.5-flash'=>'Gemini 2.5 Flash',
                                    'gemini-2.5-pro'=>'Gemini 2.5 Pro',
                                ]);
                            @endphp
                            @foreach($models as $key => $label)
                                <option value="{{ $key }}" {{ old('model', $setting->model) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="gemini-help">Flash দ্রুত ও সস্তা, Pro বেশি বুদ্ধিমান।</div>
                    </div>
                    <div class="gemini-form-group">
                        <label>🌐 Language Preference</label>
                        <select name="language" class="gemini-form-control">
                            <option value="auto" {{ $setting->language=='auto' ? 'selected':'' }}>Auto (Bangla / English Detect)</option>
                            <option value="bn" {{ $setting->language=='bn' ? 'selected':'' }}>Bengali (বাংলা)</option>
                            <option value="en" {{ $setting->language=='en' ? 'selected':'' }}>English</option>
                        </select>
                    </div>
                </div>

                <div class="gemini-row">
                    <div class="gemini-form-group">
                        <label>🌡️ Temperature (Creativity 0-2)</label>
                        <input type="number" step="0.01" min="0" max="2" name="temperature" value="{{ old('temperature',$setting->temperature) }}" class="gemini-form-control">
                        <div class="gemini-help">0 = precise, 1 = creative, 0.7 balanced.</div>
                    </div>
                    <div class="gemini-form-group">
                        <label>📝 Max Output Tokens</label>
                        <input type="number" name="max_output_tokens" value="{{ old('max_output_tokens',$setting->max_output_tokens) }}" class="gemini-form-control">
                        <div class="gemini-help">2048 ভালো, বেশি হলে লম্বা উত্তর।</div>
                    </div>
                </div>

                <div class="gemini-form-group">
                    <label>📜 System Prompt (AI এর আচরণ)</label>
                    <textarea name="system_prompt" rows="8" class="gemini-form-control" placeholder="System instruction for Gemini...">{{ old('system_prompt', $setting->system_prompt) }}</textarea>
                    <div class="gemini-help">AI কিভাবে কথা বলবে, কী কী জানে - তা এখানে লিখুন। ডিফল্ট প্রম্পট ভালোভাবে কাজ করে।</div>
                </div>

                <div class="gemini-row">
                    <div class="gemini-form-group">
                        <label class="gemini-switch">
                            <input type="checkbox" name="status" value="1" {{ $setting->status ? 'checked' : '' }}>
                            <span>Active Status (Enable Assistant)</span>
                        </label>
                    </div>
                    <div class="gemini-form-group">
                        <label class="gemini-switch">
                            <input type="checkbox" name="include_store_data" value="1" {{ $setting->include_store_data ? 'checked' : '' }}>
                            <span>Include Live Store Data</span>
                        </label>
                    </div>
                    <div class="gemini-form-group">
                        <label class="gemini-switch">
                            <input type="checkbox" name="log_conversation" value="1" {{ $setting->log_conversation ? 'checked' : '' }}>
                            <span>Log Conversations</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
                    <button type="submit" class="gemini-btn-primary"><i class="fe-save"></i> Save Settings</button>
                    <button type="button" id="btnTest" class="gemini-btn-secondary"><i class="fe-zap"></i> Test Connection</button>
                    <a href="{{ route('admin.gemini.index') }}" class="gemini-btn-secondary">Cancel</a>
                </div>

                <div id="testResult" class="gemini-test-result"></div>

            </form>

            <hr style="margin:24px 0;">

            <div style="font-size:12.5px;color:#4b5563;line-height:1.7;">
                <h6 style="font-weight:700;color:#111827;font-size:14px;">📘 How to setup?</h6>
                <ol style="margin-left:18px;">
                    <li>Go to <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a> and login with Google.</li>
                    <li>Click <b>Create API Key</b> and copy it.</li>
                    <li>Paste here and save. Then go to Assistant and ask anything in Bangla/English.</li>
                    <li>Alternatively set <code>GEMINI_API_KEY</code> in <code>.env</code> file: <br><code>GEMINI_API_KEY=AIzaSy...</code> <br><code>GEMINI_MODEL=gemini-2.5-flash</code></li>
                    <li>Cost: Gemini 2.5 Flash is highly capable and cost-effective for production use.</li>
                </ol>

                <h6 style="font-weight:700;margin-top:16px;color:#111827;">💡 Quick Ideas What You Can Ask:</h6>
                <ul style="margin-left:18px;">
                    <li>আজ কত অর্ডার এসেছে? রেভিনিউ কত?</li>
                    <li>Pending products কিভাবে approve করব?</li>
                    <li>Vendor verification system বুঝিয়ে বলো</li>
                    <li>একটা প্রোডাক্টের SEO description লিখে দাও</li>
                    <li>Campaign / Landing page কিভাবে বানাবো?</li>
                    <li>Fraud API কাজ করে না, কী করব?</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function(){
    $('#btnTest').on('click', function(){
        const $btn = $(this);
        const apiKey = $('input[name="api_key"]').val();
        const model = $('select[name="model"]').val();
        if(!apiKey){
            toastr.error('Please enter API Key first');
            return;
        }
        $btn.prop('disabled',true).html('<i class="fe-loader fe-spin"></i> Testing...');
        $('#testResult').hide().removeClass('success error');

        $.ajax({
            url: "{{ route('admin.gemini.settings.test') }}",
            method: "POST",
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { api_key: apiKey, model: model },
            success: function(res){
                if(res.success){
                    $('#testResult').addClass('success').html('✅ <b>Success!</b> Connection OK.<br><small>'+ (res.message?.substring(0,400) || 'Connected') +'</small>').show();
                    toastr.success('Gemini Connected!');
                } else {
                    $('#testResult').addClass('error').html('❌ <b>Failed!</b><br><small>'+ (res.message || 'Unknown error') +'</small>').show();
                    toastr.error('Connection failed');
                }
            },
            error: function(xhr){
                let msg = 'Test failed';
                try { msg = xhr.responseJSON?.message || xhr.responseText.substring(0,500); } catch(e){}
                $('#testResult').addClass('error').html('❌ <b>Error!</b><br><small>'+msg+'</small>').show();
                toastr.error('Test failed');
            },
            complete: function(){
                $btn.prop('disabled',false).html('<i class="fe-zap"></i> Test Connection');
            }
        });
    });
});
</script>
@endsection
