<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>เลือกบริษัท - {{ config('app.name') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('assets/img/innobic.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Filament Styles -->
        <link rel="stylesheet" href="{{ asset('css/filament/support/support.css') }}">
        <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
        
        <style>
            body { font-family: 'Sarabun', 'Inter', system-ui, sans-serif; }
            .company-card {
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .company-card:hover {
                border-color: rgb(59 130 246);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }
            .company-card.selected {
                border-color: rgb(59 130 246);
                background: rgb(239 246 255);
                box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
            }
            .company-logo {
                width: 60px;
                height: 60px;
                border-radius: 8px;
                background: #f3f4f6;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .company-logo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .company-name {
                font-size: 18px;
                font-weight: 600;
                color: #1f2937;
            }
            .company-description {
                font-size: 14px;
                color: #6b7280;
                margin-top: 4px;
            }
            .btn-check:checked + .company-card {
                border-color: rgb(59 130 246) !important;
                background: rgb(239 246 255) !important;
                box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3) !important;
            }
            .submit-btn {
                background: linear-gradient(135deg, rgb(59 130 246), rgb(37 99 235));
                border: none;
                color: white;
                padding: 16px 32px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);
            }
            .submit-btn:hover {
                background: linear-gradient(135deg, rgb(37 99 235), rgb(29 78 216));
                transform: translateY(-1px);
                box-shadow: 0 8px 25px 0 rgba(59, 130, 246, 0.5);
            }
        </style>
    </head>

    <body class="h-full">
        <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <img class="mx-auto h-20 w-auto" src="{{ asset('assets/img/innobic.png') }}" alt="Innobic">
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">เลือกบริษัท</h2>
                    <p class="mt-2 text-sm text-gray-600">กรุณาเลือกบริษัทที่ต้องการเข้าใช้งาน</p>
                </div>

                <!-- Company Selection -->
                <div class="bg-white py-8 px-4 shadow-xl sm:rounded-lg sm:px-10">
                    @if (session('success'))
                        <div class="mb-6 rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($companies->count() > 0)
                        <form action="{{ route('company.set') }}" method="POST" class="space-y-6">
                            @csrf
                            <div class="space-y-4">
                                @foreach ($companies as $company)
                                    <div class="company-option">
                                        <input type="radio" 
                                               class="btn-check sr-only" 
                                               name="company_id" 
                                               id="company_{{ $company->id }}" 
                                               value="{{ $company->id }}"
                                               @if($currentCompany && $currentCompany->id == $company->id) checked @endif
                                               required>
                                        <label class="company-card w-full p-6 block cursor-pointer" 
                                               for="company_{{ $company->id }}">
                                            <div class="flex items-center">
                                                <div class="company-logo mr-4">
                                                    <img src="{{ asset('assets/img/innobic.png') }}" 
                                                         alt="{{ $company->display_name ?? $company->name }}">
                                                </div>
                                                <div class="flex-grow">
                                                    <div class="company-name">{{ $company->display_name ?? $company->name }}</div>
                                                    <div class="company-description">{{ $company->name }}</div>
                                                    @if ($company->description)
                                                        <div class="company-description">{{ $company->description }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @if($currentCompany && $currentCompany->id == $company->id)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                            ปัจจุบัน
                                                        </span>
                                                    @endif
                                                    <svg class="w-5 h-5 text-gray-400 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="submit-btn w-full flex justify-center items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    เข้าใช้งานระบบ
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m5 0V9"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">ไม่พบบริษัทที่สามารถเข้าใช้งานได้</h3>
                            <p class="mt-2 text-sm text-gray-500">กรุณาติดต่อผู้ดูแลระบบเพื่อขออนุญาตเข้าใช้งาน</p>
                        </div>
                    @endif
                </div>

                @if($currentCompany)
                    <div class="text-center mt-6">
                        <form action="{{ route('company.clear') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                ออกจากบริษัทปัจจุบัน
                            </button>
                        </form>
                    </div>
                @endif

                <div class="text-center mt-8">
                    <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-blue-600 hover:text-blue-500">
                        ← กลับไปหน้า Login
                    </a>
                </div>
            </div>
        </div>

        <script>
            // Add click animation for company cards
            document.querySelectorAll('.company-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.company-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
        </script>
    </body>
</html>