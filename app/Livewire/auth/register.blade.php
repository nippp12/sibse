@section('title','Daftar Akun Baru')
<div>
    <section class="bg-white dark:bg-gray-900">
        <div class="h-[90vh] flex flex-col justify-center  md:flex-row">
            <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-6 py-8 mx-auto max-w-screen md lg:py-0">
                <div class="hidden md:block">
                    <img class="w-full"
                      src="{{ asset('assets/images/login.png')}}"
                      alt="dashboard image">
                </div>
            </div>
        <!-- Kolom Kiri -->
            <div class="w-full md:w-1/2 item-center flex flex-col items-center justify-center px-3 py-8 mx-auto max-w-screen md lg:py-0">
              <div class="w-full bg-white border rounded-lg shadow-xl dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                  <div class="p-4 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class=" text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                      Daftar Akun Baru
                    </h1>
                    <form wire:submit="register" class="space-y-4 md:space-y-6">
                      <div>
                          <x-input
                              label="Nama Lengkap"
                              placeholder="nama lengkap"
                              rounded="lg"
                              wire:model="name"
                          />
                      </div>
                      <div>
                          <x-input
                              label="Email"
                              placeholder="alamat email"
                              rounded="lg"
                              wire:model="email"
                          />
                      </div>
                      <div>
                        <x-phone
                            id="multiple-mask"
                            label="Nomor Whatsapp"
                            placeholder="nomor whatsapp"
                            rounded="lg"
                            wire:model="no_hp"
                            :mask="['####-####-######', '+## ###-####-####']"
                        />
                      </div>
                      <div>
                          <x-password
                              label="Password"
                              placeholder="password"
                              rounded="lg"
                              wire:model="password"
                          />
                      </div>
                      <div>
                          <x-password
                              label="Ulangi Password"
                              placeholder="ulangi password"
                              rounded="lg"
                              wire:model="passwordConfirmation"
                          />
                      </div>
                      <div class="flex items-start">
                        <x-checkbox id="label-tems" wire:model="acceptTerms" value="1" label="I accept the Terms and Conditions"/>
                      </div>

                      <x-button md primary label="Daftar" full rounded="lg" type="submit"/>

                      <x-btn-login-google label="Register dengan Google"/>

                      <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                        Sudah punya akun? <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary-600 hover:underline dark:text-primary-500">Masuk sekarang.</a>
                      </p>
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </section>
</div>
