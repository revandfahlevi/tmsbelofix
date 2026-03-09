// Sekarang (pakai Laravel API kita)
const ACTIVE_ADAPTER: AdapterType = 'tms-api'

// Nanti kalau ternyata SAP
const ACTIVE_ADAPTER: AdapterType = 'sap'

// Nanti kalau ternyata Odoo
const ACTIVE_ADAPTER: AdapterType = 'odoo'

// Development / demo tanpa backend
const ACTIVE_ADAPTER: AdapterType = 'mock'

1. **Dapatkan API Key:**
   - Buka [Google Cloud Console](https://console.cloud.google.com/).
   - Buat project baru atau pilih project yang ada.
   - Aktifkan **Directions API**.
   - Generate Credentials (API Key).

2. **Setup Environment:**
   Tambahkan API Key yang sudah didapat ke dalam file `.env` di backend Laravel:
   ```env
   GOOGLE_MAPS_API_KEY=AIzaSyYourRealApiKeyHere123456789