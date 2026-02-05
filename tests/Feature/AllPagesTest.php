<?php

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\ShootingActivity;
use App\Models\UserFirearm;
use App\Models\LoadData;
use App\Models\MemberDocument;
use App\Models\DocumentType;
use App\Models\LearningCategory;
use App\Models\LearningArticle;
use App\Models\EndorsementRequest;
use App\Models\TermsVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test data that routes might need
    $this->membershipType = MembershipType::create([
        'slug' => 'standard',
        'name' => 'Standard Membership',
        'description' => 'Standard membership type',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);
    
    $this->certificateType = CertificateType::create([
        'slug' => 'membership-card',
        'name' => 'Membership Card',
        'description' => 'Membership card certificate',
        'template' => 'documents.membership-card',
        'is_active' => true,
    ]);
    
    $this->documentType = DocumentType::create([
        'slug' => 'id-document',
        'name' => 'ID Document',
        'description' => 'Identity document',
        'is_required' => false,
        'is_active' => true,
    ]);
    
    $this->learningCategory = LearningCategory::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'description' => 'Test category description',
        'dedicated_type' => 'both',
        'sort_order' => 1,
    ]);
    
    $this->learningArticle = LearningArticle::create([
        'learning_category_id' => $this->learningCategory->id,
        'title' => 'Test Article',
        'slug' => 'test-article',
        'content' => '<p>Test article content</p>',
        'dedicated_type' => 'both',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    
    // Create test users for different roles
    $this->member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email_verified_at' => now(),
    ]);
    
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'email_verified_at' => now(),
    ]);
    
    $this->owner = User::factory()->create([
        'role' => User::ROLE_OWNER,
        'email_verified_at' => now(),
    ]);
    
    $this->developer = User::factory()->create([
        'role' => User::ROLE_DEVELOPER,
        'email_verified_at' => now(),
    ]);
    
    // Create active membership for member
    Membership::create([
        'user_id' => $this->member->id,
        'membership_type_id' => $this->membershipType->id,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addYear(),
        'applied_at' => now()->subMonth(),
        'approved_at' => now()->subMonth(),
        'activated_at' => now()->subMonth(),
    ]);
    
    // Create test data owned by member
    $this->memberCertificate = Certificate::create([
        'user_id' => $this->member->id,
        'certificate_type_id' => $this->certificateType->id,
        'membership_id' => $this->member->activeMembership->id,
        'certificate_number' => 'TEST-' . strtoupper(Str::random(8)),
        'issued_at' => now(),
    ]);
    
    $this->memberKnowledgeTest = KnowledgeTest::create([
        'name' => 'Test Knowledge Test',
        'slug' => 'test-knowledge-test',
        'description' => 'Test description',
        'is_active' => true,
    ]);
    
    $this->memberActivity = ShootingActivity::create([
        'user_id' => $this->member->id,
        'activity_date' => now()->subDay(),
        'location' => 'Test Range',
        'rounds_fired' => 50,
    ]);
    
    $this->memberFirearm = UserFirearm::create([
        'uuid' => Str::uuid(),
        'user_id' => $this->member->id,
        'make' => 'Test Make',
        'model' => 'Test Model',
    ]);
    
    $this->memberLoadData = LoadData::create([
        'user_id' => $this->member->id,
        'name' => 'Test Load',
        'calibre' => '9mm',
    ]);
    
    $this->memberDocument = MemberDocument::create([
        'user_id' => $this->member->id,
        'document_type_id' => $this->documentType->id,
        'original_filename' => 'test.pdf',
        'file_path' => 'test/test.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'verified' => false,
    ]);
    
    $this->memberEndorsement = EndorsementRequest::create([
        'uuid' => Str::uuid(),
        'user_id' => $this->member->id,
        'firearm_id' => $this->memberFirearm->id,
        'status' => 'draft',
        'request_type' => 'new',
    ]);
    
    // Create active terms version
    TermsVersion::create([
        'version' => '1.0',
        'title' => 'Terms and Conditions v1.0',
        'is_active' => true,
        'html_content' => '<p>Test terms</p>',
        'published_at' => now(),
    ]);
    
    // Accept terms for member
    $this->member->termsAcceptances()->create([
        'terms_version_id' => TermsVersion::active()->id,
        'accepted_at' => now(),
    ]);
});

test('all public routes return no 500 errors', function () {
    $routes = Route::getRoutes();
    $failedRoutes = [];
    
    // Helper function to replace route parameters
    $replaceParams = function (string $uri) {
        // For public routes, we'll skip parameterized routes or use defaults
        return $uri;
    };
    
    foreach ($routes as $route) {
        // Skip API routes
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        
        // Skip routes that require POST/PUT/DELETE (we're only testing GET)
        if (!in_array('GET', $route->methods())) {
            continue;
        }
        
        // Skip routes that are only available in non-production
        if (str_contains($route->uri(), 'dev/') && !in_array(app()->environment(), ['local', 'development', 'testing'])) {
            continue;
        }
        
        // Skip test routes
        if (str_contains($route->uri(), 'test-')) {
            continue;
        }
        
        // Skip routes that require authentication
        $middleware = $route->gatherMiddleware();
        if (in_array('auth', $middleware)) {
            continue;
        }
        
        // Skip routes with parameters (we'll test those in authenticated tests)
        if (preg_match('/\{[^}]+\}/', $route->uri())) {
            continue;
        }
        
        try {
            $response = $this->get($route->uri());
            
            // Get status code - handle different response types
            $statusCode = null;
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                $statusCode = $response->status();
            } elseif (property_exists($response, 'statusCode')) {
                $statusCode = $response->statusCode;
            }
            
            // Check specifically for 500 errors (ignore 404, 403, etc.)
            if ($statusCode === 500) {
                $errorMessage = 'Unknown error';
                if (method_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                } elseif (property_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                }
                
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri(),
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            // If route requires parameters and we don't have them, that's expected
            if (str_contains($e->getMessage(), 'Missing required parameter')) {
                continue;
            }
            
            // Only catch actual 500 errors, not expected exceptions (404, 403, etc.)
            $message = $e->getMessage();
            if (str_contains($message, '500') || 
                str_contains($message, 'Internal Server Error') ||
                ($e->getCode() === 500)) {
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri(),
                    'status' => 'exception',
                    'error' => $message,
                ];
            }
        }
    }
    
    expect($failedRoutes)->toBeEmpty()
        ->and($failedRoutes)->toBeEmpty('Found 500 errors on routes: ' . json_encode($failedRoutes, JSON_PRETTY_PRINT));
});

test('all authenticated member routes return no 500 errors', function () {
    $routes = Route::getRoutes();
    $failedRoutes = [];
    
    $member = $this->member;
    $test = $this; // Capture $this before closure
    $this->actingAs($member);
    
    // Helper function to replace route parameters - captures variables from parent scope
    $replaceParams = function (string $uri) use ($member, $test) {
        $replacements = [
            '{certificate}' => $test->memberCertificate->id,
            '{test}' => $test->memberKnowledgeTest->id,
            '{attempt}' => KnowledgeTestAttempt::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $member->id,
                'knowledge_test_id' => $test->memberKnowledgeTest->id,
                'started_at' => now()->subMinutes(10),
                'submitted_at' => now(),
                'auto_score' => 80,
                'manual_score' => 0,
                'total_score' => 80,
                'passed' => true,
            ])->id,
            '{activity}' => $test->memberActivity->id,
            '{firearm}' => $test->memberFirearm->id,
            '{load}' => $test->memberLoadData->id,
            '{document}' => $test->memberDocument->id,
            '{request}' => $test->memberEndorsement->id,
            '{user}' => $member->id,
            '{membership}' => $member->activeMembership?->id ?? $test->member->activeMembership?->id,
            '{category}' => $test->learningCategory->id,
            '{article}' => $test->learningArticle->id,
            '{version}' => TermsVersion::active()->id,
            '{qr_code}' => 'test-qr-code',
            '{reference}' => $test->memberEndorsement->letter_reference ?? 'TEST-REF-001',
        ];
        
        foreach ($replacements as $param => $value) {
            $uri = str_replace($param, (string) $value, $uri);
        }
        
        return $uri;
    };
    
    foreach ($routes as $route) {
        // Skip API routes
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        
        // Skip routes that require POST/PUT/DELETE
        if (!in_array('GET', $route->methods())) {
            continue;
        }
        
        // Skip dev/test routes
        if (str_contains($route->uri(), 'dev/') || str_contains($route->uri(), 'test-')) {
            continue;
        }
        
        // Skip routes that require admin/owner/developer roles
        $middleware = $route->gatherMiddleware();
        if (in_array('admin', $middleware) || 
            in_array('owner', $middleware) || 
            in_array('developer', $middleware)) {
            continue;
        }
        
        // Skip routes that don't require auth (tested in public test)
        if (!in_array('auth', $middleware)) {
            continue;
        }
        
        try {
            // Replace route parameters with test data
            $uri = $replaceParams($route->uri());
            
            $response = $this->get($uri);
            
            // Get status code - handle different response types
            $statusCode = null;
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                $statusCode = $response->status();
            } elseif (property_exists($response, 'statusCode')) {
                $statusCode = $response->statusCode;
            }
            
            // Check specifically for 500 errors (ignore 404, 403, etc.)
            if ($statusCode === 500) {
                $errorMessage = 'Unknown error';
                if (method_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                } elseif (property_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                }
                
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $uri,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            // If route requires parameters and we don't have them, that's expected
            if (str_contains($e->getMessage(), 'Missing required parameter')) {
                continue;
            }
            
            // Only catch actual 500 errors, not expected exceptions (404, 403, etc.)
            $message = $e->getMessage();
            if (str_contains($message, '500') || 
                str_contains($message, 'Internal Server Error') ||
                ($e->getCode() === 500)) {
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri() ?? $uri,
                    'status' => 'exception',
                    'error' => $message,
                ];
            }
        }
    }
    
    expect($failedRoutes)->toBeEmpty()
        ->and($failedRoutes)->toBeEmpty('Found 500 errors on member routes: ' . json_encode($failedRoutes, JSON_PRETTY_PRINT));
});

test('all authenticated admin routes return no 500 errors', function () {
    $routes = Route::getRoutes();
    $failedRoutes = [];
    
    $admin = $this->admin;
    $test = $this; // Capture $this before closure
    $this->actingAs($admin);
    
    $replaceParams = function (string $uri) use ($admin, $test) {
        $replacements = [
            '{certificate}' => $test->memberCertificate->id,
            '{test}' => $test->memberKnowledgeTest->id,
            '{attempt}' => KnowledgeTestAttempt::factory()->create([
                'user_id' => $test->member->id,
                'knowledge_test_id' => $test->memberKnowledgeTest->id,
            ])->id,
            '{activity}' => $test->memberActivity->id,
            '{firearm}' => $test->memberFirearm->id,
            '{load}' => $test->memberLoadData->id,
            '{document}' => $test->memberDocument->id,
            '{request}' => $test->memberEndorsement->id,
            '{user}' => $test->member->id,
            '{membership}' => $test->member->activeMembership?->id,
            '{category}' => $test->learningCategory->id,
            '{article}' => $test->learningArticle->id,
            '{version}' => TermsVersion::active()->id,
            '{qr_code}' => 'test-qr-code',
            '{reference}' => $test->memberEndorsement->letter_reference ?? 'TEST-REF-001',
        ];
        
        foreach ($replacements as $param => $value) {
            $uri = str_replace($param, (string) $value, $uri);
        }
        
        return $uri;
    };
    
    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        
        if (!in_array('GET', $route->methods())) {
            continue;
        }
        
        if (str_contains($route->uri(), 'dev/') || str_contains($route->uri(), 'test-')) {
            continue;
        }
        
        // Only test admin routes
        $middleware = $route->gatherMiddleware();
        if (!in_array('admin', $middleware) && !str_starts_with($route->uri(), 'admin/')) {
            continue;
        }
        
        // Skip owner/developer only routes
        if (in_array('owner', $middleware) || in_array('developer', $middleware)) {
            continue;
        }
        
        try {
            $uri = $replaceParams($route->uri());
            
            $response = $this->get($uri);
            
            // Get status code - handle different response types
            $statusCode = null;
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                $statusCode = $response->status();
            } elseif (property_exists($response, 'statusCode')) {
                $statusCode = $response->statusCode;
            }
            
            // Check specifically for 500 errors (ignore 404, 403, etc.)
            if ($statusCode === 500) {
                $errorMessage = 'Unknown error';
                if (method_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                } elseif (property_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                }
                
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $uri,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            // If route requires parameters and we don't have them, that's expected
            if (str_contains($e->getMessage(), 'Missing required parameter')) {
                continue;
            }
            
            // Only catch actual 500 errors, not expected exceptions (404, 403, etc.)
            $message = $e->getMessage();
            if (str_contains($message, '500') || 
                str_contains($message, 'Internal Server Error') ||
                ($e->getCode() === 500)) {
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri() ?? $uri,
                    'status' => 'exception',
                    'error' => $message,
                ];
            }
        }
    }
    
    expect($failedRoutes)->toBeEmpty()
        ->and($failedRoutes)->toBeEmpty('Found 500 errors on admin routes: ' . json_encode($failedRoutes, JSON_PRETTY_PRINT));
});

test('all authenticated owner routes return no 500 errors', function () {
    $routes = Route::getRoutes();
    $failedRoutes = [];
    
    $owner = $this->owner;
    $test = $this; // Capture $this before closure
    $this->actingAs($owner);
    
    $replaceParams = function (string $uri) use ($owner, $test) {
        $replacements = [
            '{certificate}' => $test->memberCertificate->id,
            '{test}' => $test->memberKnowledgeTest->id,
            '{attempt}' => KnowledgeTestAttempt::factory()->create([
                'user_id' => $test->member->id,
                'knowledge_test_id' => $test->memberKnowledgeTest->id,
            ])->id,
            '{activity}' => $test->memberActivity->id,
            '{firearm}' => $test->memberFirearm->id,
            '{load}' => $test->memberLoadData->id,
            '{document}' => $test->memberDocument->id,
            '{request}' => $test->memberEndorsement->id,
            '{user}' => $test->member->id,
            '{membership}' => $test->member->activeMembership?->id,
            '{category}' => $test->learningCategory->id,
            '{article}' => $test->learningArticle->id,
            '{version}' => TermsVersion::active()->id,
            '{qr_code}' => 'test-qr-code',
            '{reference}' => $test->memberEndorsement->letter_reference ?? 'TEST-REF-001',
        ];
        
        foreach ($replacements as $param => $value) {
            $uri = str_replace($param, (string) $value, $uri);
        }
        
        return $uri;
    };
    
    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        
        if (!in_array('GET', $route->methods())) {
            continue;
        }
        
        if (str_contains($route->uri(), 'dev/') || str_contains($route->uri(), 'test-')) {
            continue;
        }
        
        // Only test owner routes
        $middleware = $route->gatherMiddleware();
        if (!in_array('owner', $middleware) && !str_starts_with($route->uri(), 'owner/')) {
            continue;
        }
        
        // Skip developer only routes
        if (in_array('developer', $middleware)) {
            continue;
        }
        
        try {
            $uri = $replaceParams($route->uri());
            
            $response = $this->get($uri);
            
            // Get status code - handle different response types
            $statusCode = null;
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                $statusCode = $response->status();
            } elseif (property_exists($response, 'statusCode')) {
                $statusCode = $response->statusCode;
            }
            
            // Check specifically for 500 errors (ignore 404, 403, etc.)
            if ($statusCode === 500) {
                $errorMessage = 'Unknown error';
                if (method_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                } elseif (property_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                }
                
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $uri,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            // If route requires parameters and we don't have them, that's expected
            if (str_contains($e->getMessage(), 'Missing required parameter')) {
                continue;
            }
            
            // Only catch actual 500 errors, not expected exceptions (404, 403, etc.)
            $message = $e->getMessage();
            if (str_contains($message, '500') || 
                str_contains($message, 'Internal Server Error') ||
                ($e->getCode() === 500)) {
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri() ?? $uri,
                    'status' => 'exception',
                    'error' => $message,
                ];
            }
        }
    }
    
    expect($failedRoutes)->toBeEmpty()
        ->and($failedRoutes)->toBeEmpty('Found 500 errors on owner routes: ' . json_encode($failedRoutes, JSON_PRETTY_PRINT));
});

test('all authenticated developer routes return no 500 errors', function () {
    $routes = Route::getRoutes();
    $failedRoutes = [];
    
    $developer = $this->developer;
    $test = $this; // Capture $this before closure
    $this->actingAs($developer);
    
    $replaceParams = function (string $uri) use ($developer, $test) {
        $replacements = [
            '{certificate}' => $test->memberCertificate->id,
            '{test}' => $test->memberKnowledgeTest->id,
            '{attempt}' => KnowledgeTestAttempt::factory()->create([
                'user_id' => $test->member->id,
                'knowledge_test_id' => $test->memberKnowledgeTest->id,
            ])->id,
            '{activity}' => $test->memberActivity->id,
            '{firearm}' => $test->memberFirearm->id,
            '{load}' => $test->memberLoadData->id,
            '{document}' => $test->memberDocument->id,
            '{request}' => $test->memberEndorsement->id,
            '{user}' => $test->member->id,
            '{membership}' => $test->member->activeMembership?->id,
            '{category}' => $test->learningCategory->id,
            '{article}' => $test->learningArticle->id,
            '{version}' => TermsVersion::active()->id,
            '{qr_code}' => 'test-qr-code',
            '{reference}' => $test->memberEndorsement->letter_reference ?? 'TEST-REF-001',
        ];
        
        foreach ($replacements as $param => $value) {
            $uri = str_replace($param, (string) $value, $uri);
        }
        
        return $uri;
    };
    
    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        
        if (!in_array('GET', $route->methods())) {
            continue;
        }
        
        if (str_contains($route->uri(), 'dev/') || str_contains($route->uri(), 'test-')) {
            continue;
        }
        
        // Only test developer routes
        $middleware = $route->gatherMiddleware();
        if (!in_array('developer', $middleware) && !str_starts_with($route->uri(), 'developer/')) {
            continue;
        }
        
        try {
            $uri = $replaceParams($route->uri());
            
            $response = $this->get($uri);
            
            // Get status code - handle different response types
            $statusCode = null;
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                $statusCode = $response->status();
            } elseif (property_exists($response, 'statusCode')) {
                $statusCode = $response->statusCode;
            }
            
            // Check specifically for 500 errors (ignore 404, 403, etc.)
            if ($statusCode === 500) {
                $errorMessage = 'Unknown error';
                if (method_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                } elseif (property_exists($response, 'exception') && $response->exception) {
                    $errorMessage = $response->exception->getMessage();
                }
                
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $uri,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            // If route requires parameters and we don't have them, that's expected
            if (str_contains($e->getMessage(), 'Missing required parameter')) {
                continue;
            }
            
            // Only catch actual 500 errors, not expected exceptions (404, 403, etc.)
            $message = $e->getMessage();
            if (str_contains($message, '500') || 
                str_contains($message, 'Internal Server Error') ||
                ($e->getCode() === 500)) {
                $failedRoutes[] = [
                    'route' => $route->getName() ?? $route->uri(),
                    'uri' => $route->uri() ?? $uri,
                    'status' => 'exception',
                    'error' => $message,
                ];
            }
        }
    }
    
    expect($failedRoutes)->toBeEmpty()
        ->and($failedRoutes)->toBeEmpty('Found 500 errors on developer routes: ' . json_encode($failedRoutes, JSON_PRETTY_PRINT));
});


