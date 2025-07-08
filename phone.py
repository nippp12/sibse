import phonenumbers
from phonenumbers import geocoder, carrier

# Memasukkan nomor telepon dengan kode negara
number = input("Masukkan nomor telepon dengan kode negara: ")

# Mem-parsing nomor telepon
phone_number = phonenumbers.parse(number)

# Mendapatkan lokasi
location = geocoder.description_for_number(phone_number, "en")
print("Lokasi:", location)

# Mendapatkan penyedia layanan
service_provider = carrier.name_for_number(phone_number, "en")
print("Penyedia layanan:", service_provider)