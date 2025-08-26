<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
            <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                <img class="" src="{{ asset('assets/images/logo.png')}}" alt="logo">
            </a>
            <div class="w-full p-6 bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md dark:bg-gray-800 dark:border-gray-700 sm:p-8">
                <h2 class="mb-1 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    Reset Password
                </h2>
                <p class="text-slate-600 text-sm">
                    Masukkan email Anda di bawah ini untuk mengatur ulang kata sandi Anda.
                </p>
                <form wire:submit="resetPassword" class="mt-4 space-y-4 lg:mt-5 md:space-y-5">
                    <div>
                        <x-input
                            wire:model="email"
                            label="Email"
                            placeholder="masukan email"
                        />
                    </div>
                    <button type="submit" class="w-full flex justify-center text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        Reset password
                        <x-spinner target="resetPassword"/>
                    </button>
                </form>
            </div>
        </div>
      </section>
</div>
