<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $originalPublicPath;

    private string $temporaryPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalPublicPath = public_path();
        $this->temporaryPublicPath = storage_path('framework/testing/settings-media/'.Str::uuid());
        $this->app->usePublicPath($this->temporaryPublicPath);
        mkdir($this->temporaryPublicPath, 0755, true);

        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Administrator'],
            ['id' => 2, 'name' => 'guru', 'description' => 'Guru'],
            ['id' => 3, 'name' => 'siswa', 'description' => 'Siswa'],
        ]);

        $userId = DB::table('users')->insertGetId([
            'ip_address' => '127.0.0.1',
            'username' => 'admin_media_test',
            'password' => Hash::make('password123'),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Admin',
        ]);
        DB::table('users_groups')->insert(['user_id' => $userId, 'group_id' => 1]);

        $user = User::findOrFail($userId);
        $this->actingAs($user)->withSession([
            'is_login' => true,
            'user_id' => (int) $user->id,
            'role' => '1',
            'role_name' => 'admin',
        ]);

        DB::table('setting')->insert([
            'nama_aplikasi' => 'Aplikasi Tes',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryPublicPath);
        $this->app->usePublicPath($this->originalPublicPath);

        parent::tearDown();
    }

    public function test_it_saves_the_uploaded_left_logo_to_settings(): void
    {
        $this->assertMediaUploadSaved('logo_kiri');
    }

    public function test_it_saves_the_uploaded_right_logo_to_settings(): void
    {
        $this->assertMediaUploadSaved('logo_kanan');
    }

    public function test_it_saves_the_uploaded_stamp_and_removes_its_old_file(): void
    {
        $oldRelativePath = 'uploads/settings/stampel.old.png';
        File::ensureDirectoryExists(public_path('uploads/settings'));
        File::put(public_path($oldRelativePath), 'old stamp');
        DB::table('setting')->update(['stampel' => $oldRelativePath]);

        $this->assertMediaUploadSaved('stampel');

        $this->assertFileDoesNotExist(public_path($oldRelativePath));
    }

    public function test_it_saves_the_uploaded_signature_to_settings(): void
    {
        $this->assertMediaUploadSaved('tanda_tangan');
    }

    public function test_invalid_media_upload_returns_the_validation_error(): void
    {
        $response = $this->post('/settings/uploadFile/tanda_tangan', [
            'logo' => UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonPath('status', false)
            ->assertJsonStructure(['message', 'errors' => ['logo']]);
    }

    private function createTestImage(string $name, int $width, int $height): UploadedFile
    {
        $fakeImage = UploadedFile::fake()->image($name, $width, $height);
        $sourcePath = $this->temporaryPublicPath.DIRECTORY_SEPARATOR.'source-'.Str::uuid().'.png';

        copy($fakeImage->getPathname(), $sourcePath);

        return new UploadedFile($sourcePath, $name, 'image/png', UPLOAD_ERR_OK, true);
    }

    private function assertMediaUploadSaved(string $field): void
    {
        $response = $this->post('/settings/uploadFile/'.$field, [
            'logo' => $this->createTestImage($field.'.png', 120, 120),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('status', true);

        $relativePath = DB::table('setting')->value($field);
        $this->assertMatchesRegularExpression(
            '#^uploads/settings/'.preg_quote($field, '#').'\.[0-9a-f-]+\.png$#i',
            $relativePath
        );
        $this->assertFileExists(public_path($relativePath));
    }
}
