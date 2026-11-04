# STANDAR KODE & PANDUAN KEAMANAN SISTEM

## STANDARD\_CODE.md

**Pedoman Rekayasa Perangkat Lunak, Arsitektur Bersih, dan Keamanan Standar Industri (Pentest-Ready)**

**Technology Stack:** Laravel 12, PHP 8.2+, Inertia.js, Vue.js 3, MySQL/MariaDB

**Document Version:** 1.0  
**Classification:** Internal Development Standard  
**Scope:** Backend, Frontend, Database, API, Authentication, Infrastructure, and Deployment

 *

# 1\. TUJUAN DAN RUANG LINGKUP

Dokumen ini merupakan standar pengembangan perangkat lunak yang wajib menjadi acuan bagi developer dan AI coding agent.

Tujuan utama:

1. Menghasilkan kode yang bersih, konsisten, dan mudah dipelihara.
2. Menerapkan prinsip keamanan sejak tahap perancangan.
3. Mengurangi risiko kerentanan aplikasi.
4. Menjaga integritas, kerahasiaan, dan ketersediaan data.
5. Memudahkan proses code review, audit, dan penetration testing.
6. Meminimalkan technical debt.
7. Memastikan perubahan kode dapat diuji dan ditelusuri.
8. Menerapkan prinsip secure software development lifecycle (SSDLC).

Standar ini berlaku untuk:

*   Backend Laravel.
*   Frontend Vue.js.
*   Komunikasi Laravel dan Inertia.js.
*   Database.
*   REST API.
*   Authentication dan authorization.
*   File upload.
*   Third-party integration.
*   Background jobs.
*   Deployment dan konfigurasi server.

**Prinsip utama:**

> Security by Design, Secure by Default, Least Privilege, Defense in Depth, dan Fail Securely.

 *

# 2\. HIERARKI ATURAN PENGEMBANGAN

Setiap pengembangan wajib mengikuti urutan prioritas berikut:

1. Keamanan sistem dan integritas data.
2. Kebutuhan bisnis.
3. Kebenaran fungsional.
4. Konsistensi arsitektur.
5. Maintainability.
6. Performance.
7. Kemudahan implementasi.

Solusi yang cepat tetapi mengorbankan keamanan tidak diperbolehkan.

## 2.1 Integrasi dengan [AGENTS.md](http://AGENTS.md)

Seluruh AI coding agent wajib mengikuti:

*   `AGENTS.md` untuk aturan operasional.
*   `STANDARD_CODE.md` untuk standar implementasi.

Ketentuan penting:

*   AI wajib menganalisis sebelum mengusulkan perubahan.
*   AI tidak boleh mengubah kode tanpa persetujuan.
*   AI tidak boleh menjalankan terminal command tanpa persetujuan.
*   AI tidak boleh melakukan migrasi database secara otomatis.
*   AI wajib menjelaskan risiko perubahan.
*   AI wajib menjaga kompatibilitas kode yang sudah berjalan.

Dokumen ini tidak memberikan izin eksekusi otomatis.

Jika terdapat konflik antara kedua dokumen, aturan yang lebih ketat terhadap keamanan dan perubahan sistem harus diutamakan, sepanjang tidak bertentangan dengan instruksi yang lebih tinggi.

 *

# 3\. STANDAR CLEAN CODE

## 3.1 Prinsip SOLID

Kode harus menerapkan prinsip SOLID ketika relevan.

### Single Responsibility Principle

Setiap class harus memiliki tanggung jawab yang jelas.

Controller tidak boleh menjadi tempat seluruh business logic.

### Open/Closed Principle

Utamakan desain yang dapat diperluas tanpa harus mengubah banyak kode yang sudah stabil.

### Liskov Substitution Principle

Implementasi turunan harus mempertahankan kontrak dan perilaku yang diharapkan.

### Interface Segregation Principle

Gunakan interface yang spesifik ketika abstraksi benar-benar diperlukan.

### Dependency Inversion Principle

Gunakan dependency injection untuk memisahkan komponen dari implementasi konkret ketika memberikan manfaat nyata.

Hindari membuat interface untuk setiap class tanpa kebutuhan.

## 3.2 Penamaan

Gunakan nama yang jelas dan konsisten.

Contoh:

```php
class ProductController extends Controller
{
    public function index()
    {
        //
    }

    public function store()
    {
        //
    }
}
```

Aturan:

*   Class menggunakan PascalCase.
*   Method dan variable menggunakan camelCase.
*   Database table menggunakan snake\_case.
*   Database column menggunakan snake\_case.
*   Constant menggunakan UPPER\_SNAKE\_CASE.
*   Nama method harus menggambarkan tindakan.
*   Hindari singkatan yang ambigu.

## 3.3 Larangan Kode

Dilarang:

*   Hardcoded credentials.
*   Debugging statement pada production.
*   Dead code yang tidak diperlukan.
*   Duplicate business logic.
*   Silent exception handling.
*   Penggunaan global state tanpa alasan jelas.
*   Query database berulang tanpa kebutuhan.
*   Komentar yang tidak sesuai dengan perilaku kode.

Contoh debugging yang tidak boleh tertinggal:

```php
dd($user);
dump($request);
var_dump($data);
```

Frontend:

```javascript
console.log(userToken)
```

Informasi sensitif tidak boleh dicetak ke browser console.

 *

# 4\. STANDAR ARSITEKTUR LARAVEL

## 4.1 Struktur Aplikasi

Gunakan struktur Laravel yang konsisten.

Contoh:

```
app/
├── Actions/
├── Enums/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Models/
├── Policies/
├── Services/
└── Support/
```

Direktori tambahan hanya dibuat ketika diperlukan.

Tidak wajib membuat seluruh direktori sejak awal.

## 4.2 Controller

Controller bertanggung jawab untuk:

1. Menerima HTTP request.
2. Menggunakan request validation.
3. Memanggil business logic.
4. Mengembalikan response.

Controller tidak boleh berisi business logic kompleks.

Contoh:

```php
public function store(
    StoreProductRequest $request,
    ProductService $service
) {
    $product = $service->create(
        $request->validated()
    );

    return redirect()
        ->route('products.index')
        ->with('success', 'Produk berhasil dibuat.');
}
```

## 4.3 Service Layer

Service digunakan untuk business logic yang kompleks atau digunakan kembali.

```php
class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create($data);
        });
    }
}
```

Service tidak boleh dibuat hanya untuk membungkus satu operasi sederhana tanpa manfaat arsitektural.

## 4.4 Form Request

Gunakan Form Request untuk validasi kompleks.

```php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Product::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

Validasi input tidak menggantikan authorization.

## 4.5 Eloquent

Wajib:

*   Menghindari N+1 query.
*   Menggunakan eager loading jika diperlukan.
*   Menggunakan pagination untuk dataset besar.
*   Melindungi mass assignment.
*   Menggunakan database transaction untuk operasi atomik.

Contoh:

```php
$products = Product::query()
    ->with('category')
    ->paginate(15);
```

Hindari penggunaan `Model::all()` pada dataset yang berpotensi besar.

 *

# 5\. STANDAR KEAMANAN APLIKASI

Aplikasi harus dirancang dengan mempertimbangkan kategori risiko OWASP Top 10 dan kontrol OWASP ASVS.

## 5.1 Broken Access Control

Setiap resource harus memiliki pemeriksaan authorization.

Tidak cukup hanya menyembunyikan tombol di frontend.

Contoh:

```php
public function update(
    UpdateProductRequest $request,
    Product $product
) {
    $this->authorize('update', $product);

    // Update product
}
```

Aturan:

*   Terapkan least privilege.
*   Terapkan deny by default.
*   Periksa ownership resource.
*   Terapkan authorization pada setiap endpoint.
*   Gunakan Laravel Policies atau Gates.
*   Jangan percaya role dari frontend.

### Pencegahan IDOR/BOLA

Pengguna tidak boleh mengakses resource milik pengguna lain tanpa izin.

Contoh:

```php
$order = Order::query()
    ->where('user_id', auth()->id())
    ->findOrFail($id);
```

Untuk aplikasi multi-tenant, pembatasan tenant wajib diterapkan pada seluruh query yang relevan.

## 5.2 Authentication

Gunakan mekanisme authentication Laravel yang didukung dan sesuai dengan arsitektur aplikasi.

Aturan:

*   Password harus di-hash menggunakan algoritma yang aman.
*   Jangan menyimpan password plaintext.
*   Terapkan login throttling.
*   Gunakan session regeneration setelah login.
*   Invalidasi session ketika logout.
*   Terapkan MFA untuk akun berisiko tinggi jika tersedia.
*   Jangan mengungkap apakah email terdaftar melalui pesan error yang tidak perlu.
*   Gunakan token reset password dengan masa berlaku terbatas.

Contoh:

```php
$request->session()->regenerate();
```

Password hashing harus menggunakan fasilitas Laravel Hash.

```php
Hash::make($password);
```

Dilarang menggunakan MD5 atau SHA-1 langsung untuk password hashing.

## 5.3 Session Security

Konfigurasi production harus mempertimbangkan:

```env
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Catatan:

*   `SESSION_SECURE_COOKIE=true` mensyaratkan HTTPS.
*   SameSite harus disesuaikan dengan kebutuhan aplikasi.
*   Pengaturan cookie tidak menggantikan perlindungan CSRF.
*   Gunakan session driver yang sesuai dengan kebutuhan deployment.

Session identifier tidak boleh disimpan dalam URL.

## 5.4 Cross-Site Scripting (XSS)

Semua input pengguna dianggap tidak tepercaya.

Vue secara default melakukan escaping pada interpolasi teks.

Contoh aman:

```vue
<template>
    <p>{{ user.name }}</p>
</template>
```

Hindari:

```vue
<template>
    <div v-html="userInput"></div>
</template>
```

`v-html` hanya boleh digunakan jika benar-benar diperlukan dan HTML telah disanitasi menggunakan mekanisme yang sesuai.

Dilarang:

*   Memasukkan input mentah ke `innerHTML`.
*   Menjalankan kode dinamis dari input pengguna.
*   Memasukkan input tidak tepercaya ke konteks JavaScript yang dapat dieksekusi.

## 5.5 SQL Injection

Gunakan Eloquent ORM atau parameter binding.

Contoh:

```php
User::query()
    ->where('email', $email)
    ->first();
```

Jika menggunakan raw query, wajib menggunakan parameter binding.

```php
DB::select(
    'SELECT * FROM users WHERE email = ?',
    [$email]
);
```

Nama tabel, nama kolom, dan arah sorting dinamis harus menggunakan allowlist.

Parameter binding tidak melindungi SQL identifier.

## 5.6 CSRF Protection

Untuk aplikasi Laravel + Inertia:

*   Pertahankan CSRF middleware.
*   Gunakan mekanisme form submission yang didukung Inertia.
*   Jangan mengecualikan seluruh route web dari CSRF.
*   Jangan menonaktifkan CSRF untuk mengatasi error 419.
*   Pastikan konfigurasi session, domain, dan cookie benar.

Endpoint API stateless harus menggunakan mekanisme authentication yang sesuai.

## 5.7 Rate Limiting

Terapkan rate limiting pada endpoint sensitif.

Contoh:

```php
Route::middleware('throttle:5,1')
    ->post('/login', [LoginController::class, 'store']);
```

Angka tersebut hanya contoh dan harus disesuaikan dengan kebutuhan sistem.

Endpoint yang perlu dipertimbangkan:

*   Login.
*   Password reset.
*   OTP verification.
*   Registration.
*   Search yang mahal.
*   Public API.
*   File upload.

Rate limiting harus mempertimbangkan identitas pengguna, alamat IP, proxy tepercaya, dan risiko penyalahgunaan.

## 5.8 Security Misconfiguration

Production wajib:

*   Menonaktifkan debug.
*   Menggunakan HTTPS.
*   Membatasi akses file sensitif.
*   Menghindari directory listing.
*   Menggunakan security headers.
*   Membatasi permission filesystem.
*   Menghindari default credentials.
*   Menghapus endpoint debugging yang tidak diperlukan.

Jangan mengekspos:

```
.env
.git/
storage/logs/
database/
vendor/
```

Web server harus diarahkan ke direktori `public/` Laravel.

 *

# 6\. STANDAR INERTIA.JS

Inertia props harus diperlakukan sebagai data yang dapat dilihat pengguna melalui browser.

Jangan pernah mengirim:

*   Password hash.
*   Access token rahasia.
*   API secret.
*   Internal credentials.
*   Sensitive configuration.
*   Data pengguna lain tanpa authorization.

Contoh:

```php
return Inertia::render('Products/Index', [
    'products' => ProductResource::collection(
        $products
    ),
]);
```

Gunakan explicit serialization atau API Resources jika diperlukan.

## 6.1 Shared Props

Shared props harus seminimal mungkin.

Hindari mengirim seluruh model User tanpa memilih atribut.

Contoh:

```php
'auth' => [
    'user' => $request->user()?->only([
        'id',
        'name',
        'email',
    ]),
],
```

Jangan menganggap props yang disembunyikan oleh UI menjadi aman.

## 6.2 Authorization

Frontend hanya bertanggung jawab atas pengalaman pengguna.

Backend tetap menjadi sumber keputusan authorization.

Menyembunyikan tombol edit tidak berarti endpoint edit aman.

 *

# 7\. STANDAR VUE.JS 3

Gunakan Composition API dan `<script setup>` jika sesuai dengan konvensi project.

## 7.1 Props

Definisikan props dengan jelas.

```vue
<script setup>
defineProps({
    products: {
        type: Array,
        required: true,
    },
})
</script>
```

Jangan memodifikasi props secara langsung.

## 7.2 Form Handling

Gunakan Inertia `useForm` ketika sesuai.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    price: '',
})

const submit = () => {
    form.post('/products')
}
</script>
```

Frontend validation hanya meningkatkan UX.

Backend validation tetap wajib.

## 7.3 Sensitive Data

Dilarang menyimpan informasi rahasia dalam:

*   Local Storage.
*   Session Storage.
*   Hardcoded JavaScript.
*   Public environment variables.
*   Browser console.

Token authentication tidak boleh disimpan dalam localStorage secara default tanpa analisis ancaman dan persetujuan arsitektur.

Untuk aplikasi web berbasis session, prioritaskan cookie yang aman dan HttpOnly.

 *

# 8\. FILE UPLOAD SECURITY

File upload merupakan area berisiko tinggi.

Wajib:

1. Validasi ukuran file.
2. Validasi tipe file berdasarkan konten.
3. Gunakan allowlist ekstensi.
4. Gunakan nama file yang dibuat server.
5. Hindari menggunakan nama file asli sebagai storage path.
6. Simpan file privat di storage nonpublik.
7. Terapkan authorization untuk akses file privat.
8. Jangan izinkan eksekusi script pada direktori upload.
9. Pertimbangkan scanning malware sesuai tingkat risiko.
10. Batasi jumlah dan frekuensi upload.

Contoh:

```php
$request->validate([
    'image' => [
        'required',
        'image',
        'mimes:jpg,jpeg,png,webp',
        'max:2048',
    ],
]);
```

Validasi Laravel harus dikombinasikan dengan konfigurasi storage dan web server yang aman.

File SVG memerlukan perhatian khusus karena dapat mengandung konten aktif.

Untuk object storage seperti Cloudflare R2:

*   Gunakan private bucket untuk file sensitif.
*   Gunakan signed URL dengan masa berlaku terbatas jika diperlukan.
*   Jangan mengekspos secret access key.
*   Terapkan batasan ukuran dan tipe upload.
*   Verifikasi authorization sebelum membuat signed URL.
*   Gunakan CORS yang spesifik sesuai kebutuhan.
*   Pertimbangkan pemeriksaan file setelah upload.

 *

# 9\. DATABASE SECURITY

## 9.1 Database Credentials

Gunakan environment variables.

Jangan menyimpan credentials dalam source code.

Database user harus menggunakan prinsip least privilege.

## 9.2 Transaction

Gunakan transaksi untuk operasi yang harus berhasil atau gagal secara bersamaan.

```php
DB::transaction(function () use ($order) {
    // Atomic operations
});
```

## 9.3 Data Integrity

Gunakan:

*   Foreign keys.
*   Unique constraints.
*   Indexes.
*   Appropriate column types.
*   Database transactions.
*   Application-level validation.

Validasi aplikasi tidak menggantikan database constraints.

## 9.4 Concurrent Operations

Untuk operasi sensitif seperti:

*   Stock management.
*   Financial transactions.
*   Inventory transfer.
*   Balance adjustments.
*   Payment processing.

Pertimbangkan:

*   Database transactions.
*   Row-level locking.
*   Idempotency.
*   Unique constraints.
*   Race condition prevention.

Contoh:

```php
DB::transaction(function () use ($productId) {
    $product = Product::query()
        ->lockForUpdate()
        ->findOrFail($productId);

    // Perform validated stock operation
});
```

Jangan memperbarui saldo atau stok menggunakan pola read-modify-write yang rentan race condition.

 *

# 10\. API SECURITY

Jika aplikasi menggunakan REST API, terapkan standar berikut.

## 10.1 Authentication

Gunakan metode yang sesuai:

*   Laravel Sanctum.
*   Session-based authentication.
*   OAuth 2.0 jika diperlukan.

Jangan membuat sistem token custom tanpa kebutuhan yang jelas.

## 10.2 Authorization

Setiap endpoint yang mengakses resource terlindungi harus memverifikasi izin pengguna.

## 10.3 Input Validation

Validasi:

*   Query parameters.
*   Request body.
*   Route parameters.
*   File uploads.
*   Sorting fields.
*   Pagination parameters.

## 10.4 Response Security

Jangan mengembalikan:

*   Stack trace.
*   Database credentials.
*   Internal configuration.
*   Sensitive exception details.
*   Unauthorized user information.

Gunakan response yang konsisten.

## 10.5 CORS

CORS bukan mekanisme authentication.

Production harus menggunakan daftar origin yang diizinkan sesuai kebutuhan.

Hindari wildcard origin untuk endpoint credentialed.

Jangan menggunakan konfigurasi CORS untuk menggantikan authorization.

 *

# 11\. ERROR HANDLING DAN LOGGING

## 11.1 Error Handling

Error harus ditangani secara konsisten.

Dilarang menampilkan internal exception detail kepada pengguna production.

Gunakan pesan yang aman dan mudah dipahami.

## 11.2 Logging

Catat peristiwa penting seperti:

*   Authentication failures.
*   Authorization failures.
*   Suspicious requests.
*   Critical business operations.
*   Unexpected application errors.
*   Security-related configuration changes.

Jangan mencatat:

*   Password.
*   Session token.
*   API secret.
*   Full payment card information.
*   Authentication headers.
*   Data pribadi yang tidak diperlukan.

## 11.3 Audit Trail

Untuk operasi penting, pertimbangkan audit log yang mencatat:

*   Actor.
*   Action.
*   Target resource.
*   Timestamp.
*   Result.
*   Request identifier.

Audit log harus dilindungi dari modifikasi tanpa izin.

 *

# 12\. DEPENDENCY SECURITY

Seluruh dependency harus berasal dari sumber tepercaya.

Wajib:

*   Memeriksa dependency sebelum instalasi.
*   Menggunakan versi yang masih didukung.
*   Menghindari dependency yang tidak diperlukan.
*   Mempertahankan lockfile.
*   Memeriksa security advisories.
*   Mengevaluasi dampak update.

Contoh command pemeriksaan:

```bash
composer audit
npm audit
```

Command hanya boleh dijalankan setelah mendapat persetujuan sesuai `AGENTS.md`.

Hindari menjalankan automatic dependency fixes tanpa review.

 *

# 13\. ENVIRONMENT DAN SECRET MANAGEMENT

File `.env` tidak boleh masuk ke version control.

Pastikan `.gitignore` dikonfigurasi dengan benar.

Aturan:

*   Jangan hardcode secret.
*   Jangan menampilkan secret dalam log.
*   Jangan membagikan secret melalui screenshot.
*   Gunakan secret berbeda untuk setiap environment.
*   Rotasi secret jika terindikasi bocor.
*   Batasi akses credentials.
*   Jangan menyimpan production secret di frontend.

`APP_KEY` harus dijaga kerahasiaannya.

Rotasi `APP_KEY` harus direncanakan karena dapat memengaruhi data terenkripsi dan session.

 *

# 14\. SECURITY HEADERS

Production harus mengevaluasi penerapan header berikut:

| Header | Tujuan |
| --- | --- |
| Content-Security-Policy | Membatasi sumber konten yang diizinkan |
| Strict-Transport-Security | Memperkuat penggunaan HTTPS |
| X-Content-Type-Options | Mencegah MIME sniffing |
| Referrer-Policy | Membatasi informasi referrer |
| Permissions-Policy | Membatasi akses fitur browser |
| X-Frame-Options | Mengurangi risiko clickjacking pada browser yang mendukung |

Gunakan:

```http
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
```

Contoh baseline CSP:

```http
Content-Security-Policy: default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'
```

CSP harus disesuaikan dengan aset, integrasi, dan kebutuhan aplikasi.

Gunakan nonce atau hash jika diperlukan untuk script inline.

Jangan langsung menerapkan CSP ketat ke production tanpa pengujian.

HSTS hanya boleh diterapkan setelah HTTPS dan cakupan subdomain dievaluasi.

 *

# 15\. PERFORMANCE DAN RELIABILITY

Keamanan dan performa harus dipertimbangkan bersama.

Aturan:

*   Hindari N+1 query.
*   Gunakan pagination.
*   Gunakan caching secara terukur.
*   Gunakan queue untuk pekerjaan berat.
*   Hindari blocking request yang panjang.
*   Gunakan timeout untuk external API.
*   Gunakan retry dengan batas yang aman.
*   Terapkan idempotency pada operasi yang berisiko diproses berulang.

Cache tidak boleh menyebabkan kebocoran data antar pengguna.

Jangan menyimpan data sensitif dalam cache bersama tanpa isolasi yang sesuai.

 *

# 16\. TESTING STANDARD

Setiap fitur penting harus memiliki pengujian yang sesuai.

## 16.1 Unit Test

Menguji business logic secara terisolasi.

## 16.2 Feature Test

Menguji:

*   Authentication.
*   Authorization.
*   Validation.
*   Controller responses.
*   Database operations.
*   Inertia responses.

## 16.3 Security Test

Pengujian harus mencakup kasus negatif, antara lain:

*   Unauthenticated access.
*   Unauthorized access.
*   Cross-user resource access.
*   Invalid input.
*   Malicious file upload.
*   CSRF protection.
*   Rate limiting.
*   Mass assignment.
*   Sensitive data exposure.

## 16.4 Regression Test

Pastikan perubahan tidak merusak fitur yang sudah berjalan.

AI tidak boleh mengklaim pengujian berhasil jika belum benar-benar dijalankan.

 *

# 17\. PENTEST READINESS CHECKLIST

Checklist ini digunakan sebelum security assessment.

## Authentication

- [ ] Password hashing aman.
- [ ] Login memiliki rate limiting.
- [ ] Session regeneration diterapkan.
- [ ] Logout menginvalidasi session.
- [ ] Password reset memiliki expiry.
- [ ] MFA dievaluasi untuk akun berisiko tinggi.

## Authorization

- [ ] Endpoint terlindungi memiliki authorization.
- [ ] IDOR/BOLA telah diuji.
- [ ] Role tidak dipercaya dari frontend.
- [ ] Cross-tenant access dicegah.
- [ ] Privilege escalation diuji.

## Input Security

- [ ] Semua input divalidasi.
- [ ] SQL injection diuji.
- [ ] XSS diuji.
- [ ] CSRF diuji.
- [ ] File upload divalidasi.
- [ ] Mass assignment diperiksa.

## Configuration

- [ ] APP\_DEBUG=false.
- [ ] HTTPS aktif.
- [ ] Security headers diperiksa.
- [ ] Directory listing nonaktif.
- [ ] File sensitif tidak dapat diakses publik.
- [ ] Credentials tidak terekspos.
- [ ] CORS dikonfigurasi sesuai kebutuhan.

## Dependencies

- [ ] Composer dependencies diperiksa.
- [ ] NPM dependencies diperiksa.
- [ ] Tidak ada known critical vulnerability yang belum ditangani atau dimitigasi.
- [ ] Runtime masih mendapatkan security support.

## Infrastructure

- [ ] Database tidak terekspos tanpa kebutuhan.
- [ ] Firewall dikonfigurasi.
- [ ] Permission filesystem dibatasi.
- [ ] Backup tersedia dan diuji.
- [ ] Logging aktif.
- [ ] Monitoring tersedia.
- [ ] Secret management diterapkan.

 *

# 18\. SECURITY VERIFICATION

Aplikasi harus diverifikasi menggunakan pendekatan berlapis.

## Static Analysis

Contoh tools:

*   PHPStan / Larastan.
*   Laravel Pint.
*   ESLint.
*   Composer Audit.
*   NPM Audit.

## Dynamic Security Testing

Evaluasi penggunaan:

*   OWASP ZAP.
*   Burp Suite.
*   Security header testing.
*   TLS configuration testing.

Pengujian aktif hanya boleh dilakukan terhadap sistem yang dimiliki atau secara eksplisit diizinkan untuk diuji.

Pengujian pada production harus mempertimbangkan risiko gangguan layanan.

## Manual Security Review

Prioritaskan:

1. Authentication.
2. Authorization.
3. Business logic.
4. File upload.
5. Payment atau financial operations.
6. Inventory operations.
7. Sensitive data exposure.
8. Server-side request forgery.
9. Race conditions.
10. Misconfiguration.

Automated scanning tidak menggantikan manual security testing.

 *

# 19\. CODE REVIEW CHECKLIST

Sebelum perubahan diterima:

### Architecture

- [ ] Sesuai arsitektur existing.
- [ ] Tidak menambah kompleksitas tanpa alasan.
- [ ] Tidak terdapat duplicate business logic.
- [ ] Separation of concerns terjaga.

### Backend

- [ ] Input divalidasi.
- [ ] Authorization diterapkan.
- [ ] Query aman.
- [ ] Transaction digunakan jika diperlukan.
- [ ] Error handling sesuai.

### Frontend

- [ ] Props tidak mengekspos data sensitif.
- [ ] Tidak menggunakan unsafe HTML rendering.
- [ ] Form validation berjalan.
- [ ] Loading dan error state ditangani.
- [ ] Responsive behavior terjaga.

### Security

- [ ] Tidak ada hardcoded secret.
- [ ] Tidak ada sensitive logging.
- [ ] Tidak ada authorization bypass.
- [ ] Tidak ada insecure file upload.
- [ ] Tidak ada dependency baru tanpa review.

### Testing

- [ ] Test relevan tersedia.
- [ ] Negative test dipertimbangkan.
- [ ] Regression risk dievaluasi.
- [ ] Hasil pengujian dilaporkan secara jujur.

 *

# 20\. DEFINITION OF DONE

Suatu fitur dianggap selesai apabila:

1. Kebutuhan bisnis terpenuhi.
2. Implementasi mengikuti arsitektur project.
3. Input validation diterapkan.
4. Authentication dan authorization sesuai kebutuhan.
5. Tidak ditemukan kerentanan yang diketahui dan belum ditangani dalam cakupan review.
6. Error handling sesuai.
7. Test relevan telah dijalankan dan lulus.
8. Tidak terdapat debugging code yang tertinggal.
9. Tidak terdapat secret dalam source code.
10. Perubahan telah direview.
11. Dokumentasi diperbarui jika diperlukan.
12. Risiko residual dicatat.

Jika pengujian belum dilakukan, status fitur tidak boleh dilaporkan sebagai sepenuhnya terverifikasi.

 *

# 21\. ATURAN KHUSUS UNTUK AI CODING AGENT

Setiap AI agent yang bekerja pada project ini wajib:

1. Membaca AGENTS.md.
2. Membaca STANDARD_CODE.md.
3. Memahami struktur project sebelum memberikan solusi.
4. Mengikuti existing coding conventions.
5. Mempertimbangkan dampak keamanan setiap perubahan.
6. Mengidentifikasi potensi kerentanan.
7. Menghindari perubahan yang tidak diperlukan.
8. Menjelaskan risiko sebelum implementasi.
9. Meminta persetujuan sebelum menjalankan command.
10. Meminta persetujuan sebelum mengubah file.
11. Meminta persetujuan sebelum mengubah database.
12. Tidak melakukan deployment otomatis.
13. Tidak mengklaim keamanan sistem tanpa verifikasi.
14. Tidak mengklaim aplikasi pentest-ready hanya berdasarkan code review.
15. Menjelaskan keterbatasan pengujian yang dilakukan.

## Required Security Review Format

Untuk setiap perubahan signifikan, AI harus menyampaikan:

### 1\. Analisis Teknis

Jelaskan kebutuhan atau masalah.

### 2\. Analisis Keamanan

Identifikasi potensi risiko:

*   Authentication.
*   Authorization.
*   Input validation.
*   Data exposure.
*   Database integrity.
*   Dependency security.

### 3\. Rencana Implementasi

Jelaskan pendekatan yang disarankan.

### 4\. File Terdampak

Sebutkan file yang perlu diubah.

### 5\. Risiko dan Mitigasi

Jelaskan risiko serta cara menguranginya.

### 6\. Rencana Pengujian

Jelaskan pengujian yang diperlukan.

### 7\. Persetujuan User

Tunggu persetujuan eksplisit sebelum menjalankan perubahan.

 *

# 22\. REFERENSI STANDAR

Dokumen ini mengacu pada prinsip dan panduan berikut:

*   OWASP Top 10.
*   OWASP Application Security Verification Standard (ASVS).
*   OWASP Web Security Testing Guide (WSTG).
*   OWASP API Security Top 10.
*   OWASP Cheat Sheet Series.
*   MITRE Common Weakness Enumeration (CWE).
*   NIST Secure Software Development Framework (SSDF).
*   Laravel Security Best Practices.
*   PHP-FIG PSR Standards.

Versi standar dan kontrol yang digunakan dalam audit formal harus ditetapkan secara eksplisit sesuai kebutuhan organisasi.

 *

# 23\. FINAL SECURITY PRINCIPLES

**SECURITY IS NOT OPTIONAL.**

Seluruh pengembangan harus mengikuti prinsip berikut:

1. Never trust user input.
2. Validate input on the server.
3. Enforce authorization on every protected resource.
4. Apply least privilege.
5. Protect sensitive information.
6. Use secure defaults.
7. Keep dependencies updated.
8. Log security-relevant events safely.
9. Test security controls.
10. Assume attackers can manipulate client-side requests.
11. Never sacrifice security for convenience.
12. Review before execution.
13. Obtain approval before modification.
14. Verify before deployment.
15. Continuously improve security.

**Secure by Design. Clean by Architecture. Verified by Testing.**

**END OF STANDARD\_CODE.md**