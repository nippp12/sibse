<div>
    <main class="relative min-h-screen flex flex-col justify-center bg-slate-50 overflow-hidden">
        <div class="w-full max-w-6xl mx-auto px-4 md:px-6 py-24">
            <div class="flex justify-center">

                <div class="max-w-md mx-auto text-center bg-white px-4 sm:px-8 py-10 rounded-xl shadow">
                    <header class="mb-6">
                        <h1 class="text-2xl font-bold mb-1">Verifikasi Akun</h1>
                        <p class="text-[15px] text-slate-500">{{ $textBody }}</p>
                        @if ($type === 'email')
                            <p wire:click="usingWhatsapp" class="text-sm text-amber-500 cursor-pointer">Gunakan Whatsapp</p>
                        @else
                            <p wire:click="usingEmail" class="text-sm text-amber-500 cursor-pointer">Gunakan Email</p>
                        @endif
                    </header>

                    <div class="mb-3 text-sm text-red-500">
                        @error('otp') {{ $message }} @enderror
                    </div>
                    
                    <form id="otp-form" wire:submit="verifyAccount">
                        <div class="flex items-center justify-center gap-3">
                            <input
                                wire:model="otp.0"
                                type="text"
                                class="w-14 h-14 text-center text-2xl font-extrabold text-slate-900 bg-slate-100 border border-transparent hover:border-slate-200 appearance-none rounded p-4 outline-none focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                                pattern="\d*" maxlength="1" required/>
                            <input
                                wire:model="otp.1"
                                type="text"
                                class="w-14 h-14 text-center text-2xl font-extrabold text-slate-900 bg-slate-100 border border-transparent hover:border-slate-200 appearance-none rounded p-4 outline-none focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                                maxlength="1" required/>
                            <input
                                wire:model="otp.2"
                                type="text"
                                class="w-14 h-14 text-center text-2xl font-extrabold text-slate-900 bg-slate-100 border border-transparent hover:border-slate-200 appearance-none rounded p-4 outline-none focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                                maxlength="1" required/>
                            <input
                                wire:model="otp.3"
                                type="text"
                                class="w-14 h-14 text-center text-2xl font-extrabold text-slate-900 bg-slate-100 border border-transparent hover:border-slate-200 appearance-none rounded p-4 outline-none focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                                maxlength="1" required/>
                        </div>
                        <div class="max-w-[260px] mx-auto mt-4">
                            <button type="submit" wire:loading.attr="disabled"
                                class="w-full inline-flex justify-center whitespace-nowrap rounded-lg bg-amber-500 px-3.5 py-2.5 text-sm font-medium text-white shadow-sm shadow-amber-950/10 hover:bg-amber-600 focus:outline-none focus:ring focus:ring-amber-300 focus-visible:outline-none focus-visible:ring focus-visible:ring-amber-300 transition-colors duration-150">Verify
                                Account
                                <x-spinner target="verifyAccount"/>
                            </button>
                        </div>
                    </form>
                    <div class="text-sm text-slate-500 mt-4">Belum menerima kode? 
                        <a class="font-medium text-amber-500 hover:text-amber-600" href="#0" wire:click="resendOtp" id="resend-link">Kirim ulang</a>
                    </div>
                    <div class="text-sm text-slate-500 mt-4">
                        <span id="countdown-container" style="display: none;">
                            Kirim ulang dalam <span id="countdown-timer">05:00</span>
                        </span>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const form = document.getElementById('otp-form')
                        const inputs = [...form.querySelectorAll('input[type=text]')]
                        const submit = form.querySelector('button[type=submit]')

                        const handleKeyDown = (e) => {
                            if (
                                !/^[0-9]{1}$/.test(e.key)
                                && e.key !== 'Backspace'
                                && e.key !== 'Delete'
                                && e.key !== 'Tab'
                                && !e.metaKey
                            ) {
                                e.preventDefault()
                            }

                            if (e.key === 'Delete' || e.key === 'Backspace') {
                                const index = inputs.indexOf(e.target);
                                if (index > 0) {
                                    inputs[index - 1].value = '';
                                    inputs[index - 1].focus();
                                }
                            }
                        }

                        const handleInput = (e) => {
                            const { target } = e
                            const index = inputs.indexOf(target)
                            if (target.value) {
                                if (index < inputs.length - 1) {
                                    inputs[index + 1].focus()
                                } else {
                                    submit.focus()
                                }
                            }
                        }

                        const handleFocus = (e) => {
                            e.target.select()
                        }

                        const handlePaste = (e) => {
                            e.preventDefault()
                            const text = e.clipboardData.getData('text')
                            if (!new RegExp(`^[0-9]{${inputs.length}}$`).test(text)) {
                                return
                            }
                            const digits = text.split('')
                            inputs.forEach((input, index) => input.value = digits[index])
                            submit.focus()
                        }

                        inputs.forEach((input) => {
                            input.addEventListener('input', handleInput)
                            input.addEventListener('keydown', handleKeyDown)
                            input.addEventListener('focus', handleFocus)
                            input.addEventListener('paste', handlePaste)
                        })
                    })    
                </script>

            </div>
        </div>
    </main>

    <x-loader target="resendOtp, verifyAccount"/>
</div>
