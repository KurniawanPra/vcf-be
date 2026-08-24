# VCF Backend API — Coding Agent Guide

## Project Overview

**VCF System API** — Digitized Vehicle Control Form (VCF) system for PT. Industri Nabati Lestari.  
The API manages incoming/outgoing vehicle inspections through a **4-step multistep workflow**:
1. **Bagian 1** (Gate Masuk): Vehicle entry gate inspection
2. **Bagian 2** (Weighbridge Masuk): Incoming weight & cargo documentation
3. **Bagian 3** (Weighbridge Keluar): Outgoing weight & cargo documentation  
4. **Bagian 4** (Gate Keluar): Gate exit inspection & completion

Each step has independent save/update/reject logic, enabling partial form completion across workflow stages.

### Tech Stack
- **Framework**: Laravel 11 / PHP 8.2+
- **Database**: MySQL/MariaDB 8.0+
- **Authentication**: Laravel Sanctum (token-based, role-based abilities)
- **Testing**: PHPUnit with Feature & Unit test suites
- **API Docs**: Postman Collection (VCF System API — PT. Industri Nabati Lestari.postman_collection.json)

---

## Architecture & Key Patterns

### Routing & API Structure

**Base URL**: `/api/`

Routes are organized into three main groups:
- **Master Data**: `/api/master/{resource}` — CRUD for Transporter, Driver, Jenis Kendaraan (Vehicle Type), Produk, Logistik, Users, and inspection items
- **VCF Workflow**: `/api/vcf/*` — Four separate controllers (VcfBagian1, VcfBagian2, VcfBagian3, VcfBagian4) handling each workflow step
- **Settings**: `/api/settings/*` — System configuration

**Route Model Binding**: Routes use implicit model binding (e.g., `{transporter}`, `{driver}`) to auto-inject eloquent models.

### Controller Conventions

**Location**: `app/Http/Controllers/API/{Master|VCF}/*Controller.php`

**Standard Methods**: Each CRUD controller implements:
- `index()` — List with filtering (search, date range via `from_date`/`to_date`, `is_active`)
- `store()` — Create with full validation (wrap in `DB::transaction()`)
- `show($id)` — Show single record
- `update($id)` — Partial update with `'sometimes'` rule in validation (wrap in `DB::transaction()`)
- `destroy($id)` — Delete (wrap in `DB::transaction()`)

**Error Handling Pattern**:
```php
try {
    // logic
    return response()->json(['message' => 'Success', 'data' => $result, 'success' => true]);
} catch (\Exception $e) {
    return response()->json(['errMsg' => $e->getMessage(), 'success' => false], 400);
}
```

**Response Format**: All endpoints return JSON with structure:
```json
{
  "message": "string",
  "data": {},
  "success": true,
  "errMsg": null
}
```

### Model Organization

**Eloquent Models**: Located in `app/Models/`

**Key Models**:
- `Vcf` — Main form hub, has many related inspection/cargo/seal models
- `VcfKelengkapanSupir`, `VcfMuatanDibawa`, `VcfMuatanDiisi`, `VcfPemeriksaanMasuk`, `VcfPemeriksaanKeluar`, etc.
  — Related entities (not traditional pivot tables) storing step-specific data
- `Transporter`, `Driver`, `JenisKendaraan` (Vehicle Type), `Produk` — Master data
- `User` — Sanctum-compatible authentication model

**Mass Assignment**: All models use `protected $fillable` — always declare fillable attributes.

**Relationships**: Use Eloquent conventions (`belongsTo`, `hasMany`, `hasOne`) for foreign key navigation.

### Authentication & Access Control

**Token-Based**: Laravel Sanctum
- User login via `POST /api/login` (AuthController) generates Sanctum token
- Requests require `Authorization: Bearer {token}` header

**Middleware-Based Access Control**:
- `AdminMiddleware` — Restricts to users with admin abilities (required for create/update/delete)
- `PetugasMiddleware` — Allows list/show routes for petugas (officers)
- Check `app/Http/Middleware/` for implementation

**User Roles**: Defined via Sanctum `abilities` (e.g., 'admin', 'petugas'). Assign in seeders or during user creation.

---

## Common Development Tasks

### Local Development Setup

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
cp .env.example .env
# Edit .env: set DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, APP_KEY

# 3. Generate app key
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Seed initial data (optional)
php artisan db:seed

# 6. Start dev server
php artisan serve
# API runs at http://localhost:8000/api
```

### Common Artisan Commands

```bash
# Migrations
php artisan migrate                 # Run pending migrations
php artisan migrate:rollback       # Rollback last batch
php artisan migrate:fresh --seed   # Reset DB + run all migrations + seed

# Seeders
php artisan db:seed                # Run DatabaseSeeder
php artisan db:seed --class=MasterDataSeeder

# Testing
./vendor/bin/phpunit              # Run all tests
./vendor/bin/phpunit tests/Feature # Run only feature tests
./vendor/bin/phpunit tests/Unit    # Run only unit tests

# Other
php artisan tinker                 # Interactive REPL for debugging models
php artisan cache:clear
php artisan config:cache
```

### Creating New Features

**New Master Data CRUD**:
1. Create migration: `php artisan make:migration create_table_name`
2. Create model: `php artisan make:model ModelName`
3. Create controller: `php artisan make:controller API/Master/ModelNameController`
4. Add routes in `routes/api.php`
5. Implement standard CRUD methods (follow existing controller pattern)
6. Add seeders if needed: `php artisan make:seeder TableNameSeeder`

**Adding VCF Workflow Step**:
1. Create migration for step-specific data
2. Create model for the step data (e.g., `VcfBagian5` if extending workflow)
3. Create controller: `VcfBagian5Controller` in `app/Http/Controllers/API/VCF/`
4. Implement show/store/update/reject methods with transaction wrapping
5. Register routes in `routes/api.php`

---

## Database & Migrations

**Migration Location**: `database/migrations/`  
**Naming Convention**: `YYYY_MM_DD_HHMMSS_action_description.php`

**Seeders**:
- `DatabaseSeeder` — Main seeder, calls other seeders
- `MasterDataSeeder` — Populates master data (Transporter, Driver, Vehicle Type, Products, etc.)
- `SettingsSeeder` — System settings initialization

**Key Tables**:
- `vcfs` — Main form records
- `users` — Authentication
- `personal_access_tokens` — Sanctum tokens
- `vcf_kelengkapan_supir`, `vcf_muatan_dibawa`, etc. — Step-specific form data

---

## Testing

**Structure**: Tests are in `/tests/` with Feature and Unit subdirectories.

**Test Naming**: `*Test.php` suffix (e.g., `VcfBagian1ControllerTest.php`)

**PHPUnit Config**: `phpunit.xml` sets testing environment (in-memory SQLite by default, see commented DB_CONNECTION in file).

**Running Tests**:
```bash
./vendor/bin/phpunit                    # All tests
./vendor/bin/phpunit tests/Feature      # Feature tests
./vendor/bin/phpunit --filter testName  # Single test
```

---

## Common Conventions & Best Practices

### Validation Rules
- Use `$request->validate([...])` in controller methods
- Use `'sometimes'` rule for partial updates to avoid overwriting unchanged fields
- Common rules: `required`, `string`, `email`, `exists:table,column`, `unique:table,column`, `date`, `numeric`

### Database Transactions
Always wrap create/update/delete in transactions:
```php
DB::transaction(function () {
    // multi-step operations
});
```

### API Query Parameters
- **Filtering**: `?search=keyword` (searches across model attributes)
- **Date Range**: `?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- **Status**: `?is_active=1` or `?is_active=0`
- **Pagination**: Implement via `paginate(15)` if needed

### Naming Conventions
- **Models**: PascalCase (e.g., `Vcf`, `VcfKelengkapanSupir`)
- **Controllers**: `{Resource}Controller`, pluralized resource (e.g., `TransporterController`)
- **Routes**: kebab-case with plurals (e.g., `/api/transporters`, `/api/vcf/bagian1`)
- **Tables**: snake_case plural (e.g., `transporters`, `vcf_kelengkapan_supir`)
- **Migrations**: describe the action (e.g., `create_transporters_table`, `add_status_to_vcfs`)

### Error Handling
- Catch generic `\Exception` in controllers, return 400 with error message
- Log exceptions: `\Log::error($e->getMessage());` for debugging
- Use meaningful error messages (e.g., "Transporter not found" instead of just the exception)

---

## File Organization

```
app/
├── Console/                           # Artisan commands
├── Http/
│   ├── Controllers/API/
│   │   ├── AuthController.php         # Login/logout
│   │   ├── Master/*                   # Master data CRUD
│   │   └── VCF/                       # VcfBagian1-4 workflow controllers
│   ├── Kernel.php                     # Middleware registration
│   └── Middleware/                    # Custom middleware (Auth, Admin, Petugas)
├── Models/                            # Eloquent models
└── Providers/                         # Service providers

database/
├── migrations/                        # Database schema
├── factories/                         # Model factories for testing
└── seeders/                           # Data seeders

routes/
├── api.php                            # All API routes

tests/
├── Feature/                           # Controller/API integration tests
└── Unit/                              # Logic unit tests
```

---

## Useful Tips & Pitfalls

### ✅ Do's
- Always use `DB::transaction()` for multi-step updates to maintain data consistency
- Use route model binding (`{transporter}`) to auto-inject models and validate existence
- Test with Postman collection before shipping (included in repo)
- Use seeders to populate test data consistently
- Add type hints and docstrings to controller methods for clarity

### ❌ Don'ts
- Don't hardcode user IDs; use `auth()->id()` or `$request->user()`
- Don't skip validation; always validate user input before DB operations
- Don't modify responses format; maintain `{message, data, success, errMsg}` structure
- Don't delete master data if it's referenced by VCF records (check foreign keys)
- Don't use raw SQL unless absolutely necessary; prefer Eloquent queries

### 🔍 Debugging
```bash
php artisan tinker              # Interactive debugging
>>> $vcf = Vcf::find(1);
>>> $vcf->bagian1()->get();

# Or check logs in storage/logs/
tail -f storage/logs/laravel.log
```

### Database Inspection
```sql
SELECT * FROM vcfs;
SELECT * FROM vcf_kelengkapan_supir WHERE vcf_id = 1;
```

---

## Related Resources

- [README.md](README.md) — Installation & basic setup
- [VCF System API — PT. Industri Nabati Lestari.postman_collection.json](VCF%20System%20API%20—%20PT.%20Industri%20Nabati%20Lestari.postman_collection.json) — Full API endpoint documentation with examples
- [app/Http/Controllers/API/](app/Http/Controllers/API/) — Reference controller implementations
- [database/migrations/](database/migrations/) — Table structure reference
- Laravel Sanctum Docs: https://laravel.com/docs/11.x/sanctum
- Eloquent ORM: https://laravel.com/docs/11.x/eloquent

---

## Next Steps for Agents

When working on this codebase, prioritize these checks:
1. **Before creating a feature**: Check if similar master data already exists (model/controller)
2. **Before database changes**: Review existing migration patterns and table relationships
3. **Before API changes**: Verify response format matches `{message, data, success, errMsg}`
4. **Before deployment**: Run full test suite and verify migrations work on clean DB
5. **Authentication**: Always check middleware requirements for new routes

---

*Last updated: May 2026 — VCF Backend v1.0*
