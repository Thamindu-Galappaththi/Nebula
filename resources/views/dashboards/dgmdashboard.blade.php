@extends('inc.app')

@section('title', 'NEBULA | Dashboard')

@section('content')
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="{{ asset('css/styles.min.css') }}">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous">
    <script nonce="{{ $cspNonce }}">
        // Intercept style element creation to add nonce for Tailwind CSS
        (function() {
            const nonce = '{{ $cspNonce }}';
            if (nonce) {
                const originalCreateElement = document.createElement;
                document.createElement = function(tagName) {
                    const element = originalCreateElement.call(document, tagName);
                    if (tagName.toLowerCase() === 'style') {
                        element.setAttribute('nonce', nonce);
                    }
                    return element;
                };
                
                // Also observe for any style elements added after Tailwind loads
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.tagName === 'STYLE' && !node.getAttribute('nonce')) {
                                node.setAttribute('nonce', nonce);
                            }
                        });
                    });
                });
                observer.observe(document.head, { childList: true, subtree: true });
            }
        })();
    </script>
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/tailwindcss.js') }}"></script>
    <script src="{{ asset('libs/chartjs/chart.min.js') }}"></script>
    <div id="pageContent" class="bg-gray-50">

        <!-- Navigation Tabs -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-1 py-3">
                    <button data-tab="overview" id="tab-overview"
                        class="px-4 py-2 rounded-lg text-sm font-medium tab-active">
                        <i class="fas fa-chart-line mr-2"></i>Overview
                    </button>
                    <button data-tab="students" id="tab-students"
                        class="px-4 py-2 rounded-lg text-sm font-medium tab-inactive">
                        <i class="fas fa-users mr-2"></i>Students
                    </button>
                    <button data-tab="revenues" id="tab-revenues"
                        class="px-4 py-2 rounded-lg text-sm font-medium tab-inactive">
                        <i class="fas fa-dollar-sign mr-2"></i>Revenues
                    </button>
                    <button data-tab="outstanding" id="tab-outstanding"
                        class="px-4 py-2 rounded-lg text-sm font-medium tab-inactive">
                        <i class="fas fa-exclamation-circle mr-2"></i>Outstanding
                    </button>
                    <button data-tab="marketing" id="tab-marketing"
                        class="px-4 py-2 rounded-lg text-sm font-medium tab-inactive">
                        <i class="fas fa-share-alt mr-2"></i>Marketing
                    </button>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Overview Tab -->
            <div id="content-overview" class="tab-content active">
                <!-- Key Metrics Cards -->
                <div class="flex gap-10">
                    <div class="stat-card bg-white p-2 rounded-xl shadow-sm border-4 border-sky-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Students</p>
                                <p class="text-2xl font-bold text-gray-900 pt-2 p-2" id="totalStudents">-</p>
                                <p class="text-sm text-green-600" id="studentChange"></p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white p-2 rounded-xl shadow-sm border-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Yearly Revenue</p>
                                <p class="text-2xl font-bold text-gray-900 pt-2" id="yearlyRevenue">-</p>
                            </div>

                        </div>
                    </div>

                    <div class="stat-card bg-white p-2 rounded-xl shadow-sm border-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Due this year</p>
                                <p class="text-2xl font-bold text-gray-900 pt-2" id="outstandingCurrentYear">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white p-2  rounded-xl shadow-sm border-4 border-red-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Outstanding</p>
                                <p class="text-2xl font-bold text-gray-900 pt-2" id="outstanding">-</p>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Quick Charts Grid -->
                <div class="grid gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Students by Location</h3>
                        <div style="height: 300px;">
                            <canvas id="studentsLocationChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue Summary Table -->
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold mb-4">Revenue Summary</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        {{ date('Y') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        {{ date('Y') - 1 }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Growth</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outstanding
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="revenueSummaryBody" class="divide-y divide-gray-200">
                                <!-- JS will populate rows here -->
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                <tr class="font-bold">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">Total</td>
                                    <td class="px-6 py-3 text-sm text-gray-900" id="totalCurrentYear">Rs. 0.00</td>
                                    <td class="px-6 py-3 text-sm text-gray-900" id="totalPreviousYear">Rs. 0.00</td>
                                    <td class="px-6 py-3 text-sm text-gray-900" id="totalGrowth">0%</td>
                                    <td class="px-6 py-3 text-sm text-gray-900" id="totalOutstanding">Rs. 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Students Tab -->
            <div id="content-students" class="tab-content">
                <!-- Filter Controls -->

                <div class="bg-white shadow-sm border-b">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-stretch">
                            <div class="filter-card">
                                <div class="flex items-center mb-1">
                                    <label class="block text-sm font-medium text-gray-700 mr-2">Year</label>
                                    <select id="yearSelect"
                                        class="border w-full border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                        @for($y = date('Y'); $y >= 2010; $y--)
                                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="flex flex-row gap-2 mb-6">
                                    <div class="filter-card p-2">
                                        <select id="studentMonthSelect"
                                            class="w-full border border-gray-300 rounded-md px-2 py-1 bg-white text-xs">
                                            <option value="">All Months</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="filter-card p-2">
                                        <select id="studentDaySelect"
                                            class="w-full border border-gray-300 rounded-md px-2 py-1 bg-white text-xs">
                                            <option value="">All Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-card flex flex-col gap-2">
                                <div class="filter-card flex flex-col gap-2">

                                    <!-- Compare and Range selectors at the top -->
                                    <div class="flex flex-row gap-2 mb-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="compareToggle" class="mr-2">
                                            <span class="text-sm font-medium text-gray-700">Compare</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="rangeSelectorToggle" class="mr-2">
                                            <span class="text-sm font-medium text-gray-700">Range</span>
                                        </label>
                                    </div>

                                    <!-- Compare year+month fields -->
                                    <div class="flex flex-col gap-1 mb-2" id="compareFields" style="display:none;">
                                        <div class="flex flex-row flex-wrap gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">From Year</label>
                                                <select id="fromYearSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                    disabled>
                                                    @for($y = date('Y'); $y >= 2010; $y--)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">From Month</label>
                                                <select id="fromMonthSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                    disabled>
                                                    <option value="">All</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">To Year</label>
                                                <select id="toYearSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                    disabled>
                                                    @for($y = date('Y'); $y >= 2010; $y--)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">To Month</label>
                                                <select id="toMonthSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                    disabled>
                                                    <option value="">All</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div id="compareRangeError" class="text-red-500 text-xs hidden">⚠ "From" must be earlier than "To"</div>
                                    </div>

                                    <!-- Range year+month fields -->
                                    <div class="flex flex-col gap-1 mb-2" id="rangeFields" style="display:none;">
                                        <div class="flex flex-row flex-wrap gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Start Year</label>
                                                <select id="rangeStartYearSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                    disabled>
                                                    @for($y = date('Y'); $y >= 2010; $y--)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Start Month</label>
                                                <select id="rangeStartMonthSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                    disabled>
                                                    <option value="">All</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">End Year</label>
                                                <select id="rangeEndYearSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                    disabled>
                                                    @for($y = date('Y'); $y >= 2010; $y--)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">End Month</label>
                                                <select id="rangeEndMonthSelect"
                                                    class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                    disabled>
                                                    <option value="">All</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div id="rangeRangeError" class="text-red-500 text-xs hidden">⚠ "Start" must be earlier than or equal to "End"</div>
                                    </div>

                                </div>
                            </div>

                            <div class="filter-card md:col-span-2 flex flex-col justify-between">

                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select id="locationSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Locations</option>
                                    <option value="Welisara">Welisara</option>
                                    <option value="Moratuwa">Moratuwa</option>
                                    <option value="Peradeniya">Peradeniya</option>
                                </select>

                                <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                <select id="courseSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Courses</option>
                                    @foreach(\App\Models\Course::all() as $course)
                                        <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="mt-4 flex justify-end">
                            <button onclick="loadStudentsData()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                Apply Filters
                            </button>
                        </div>

                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h4 class="font-semibold text-gray-900 mb-2">SLT MNIT Welisara</h4>
                        <p class="text-2xl font-bold text-blue-600" id="welisaraStudents">-</p>
                        <p class="text-sm text-gray-600">Students enrolled</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h4 class="font-semibold text-gray-900 mb-2">SLT MNIT Moratuwa</h4>
                        <p class="text-2xl font-bold text-blue-600" id="MoratuwaStudents">-</p>
                        <p class="text-sm text-gray-600">Students enrolled</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h4 class="font-semibold text-gray-900 mb-2">SLT MNIT Peradeniya</h4>
                        <p class="text-2xl font-bold text-blue-600" id="peradeniyaStudents">-</p>
                        <p class="text-sm text-gray-600">Students enrolled</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm mb-6">
                    <h3 class="text-lg font-semibold mb-4">Students by Location and Course</h3>
                    <div class="relative" style="height: 700px;">
                        <canvas id="chartCombined"></canvas>
                    </div>
                </div>

                <!-- Students Tab Upload/Download -->
                <div class="flex gap-2 mb-4">
                    <button class="px-3 py-2 bg-green-600 text-white rounded" onclick="downloadStudentTemplate()">Download
                        Student Excel Template</button>
                    <button class="px-3 py-2 bg-blue-600 text-white rounded"
                        onclick="showModal('studentUploadModal')">Upload Student Data</button>
                </div>

                <div id="studentUploadModal" style="display:none;"
                    class="fixed inset-0 flex items-center justify-center z-50 modal-overlay" aria-hidden="true">
                    <form id="studentUploadForm" enctype="multipart/form-data" class="bg-white p-6 rounded shadow w-96"
                        method="POST" action="{{ route('bulk.student.upload') }}">
                        @csrf
                        <h3 class="mb-4 font-bold text-lg">Upload Student Excel</h3>
                        <input type="file" name="student_excel" accept=".xlsx,.xls,.csv" required class="mb-4">
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="hideModal('studentUploadModal')"
                                class="px-3 py-1 bg-gray-400 text-white rounded">Cancel</button>
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Upload</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Revenues Tab -->
            <div id="content-revenues" class="tab-content">
                <!-- Filter Controls -->
                <div class="bg-white shadow-sm border-b mb-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-stretch">
                            <div class="filter-card">
                                <div class="flex items-center mb-1">
                                    <label class="block text-sm font-medium text-gray-700 mr-2">Year</label>
                                    <select id="revenueYearSelect"
                                        class="border w-full border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                        @for($y = date('Y'); $y >= 2010; $y--)
                                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="flex flex-row gap-2 mb-6">
                                    <div class="filter-card p-2">
                                        <select id="revenueMonthSelect"
                                            class="w-full border border-gray-300 rounded-md px-2 py-1 bg-white text-xs">
                                            <option value="">All Months</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="filter-card p-2">
                                        <select id="revenueDaySelect"
                                            class="w-full border border-gray-300 rounded-md px-2 py-1 bg-white text-xs">
                                            <option value="">All Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-card flex flex-col gap-2">
                                <div class="filter-card flex flex-col gap-2">
                                    <div class="flex flex-row gap-2 mb-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="revenueCompareToggle" class="mr-2">
                                            <span class="text-sm font-medium text-gray-700">Compare</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="revenueRangeSelectorToggle" class="mr-2">
                                            <span class="text-sm font-medium text-gray-700">Range</span>
                                        </label>
                                    </div>
                                    <!-- Compare year+month fields -->
                                    <div class="flex flex-col gap-1 mb-2" id="revenueCompareFields" style="display:none;">
                                        <div class="flex flex-row flex-wrap gap-2">
                                            <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">From Year</label>
                                            <select id="revenueFromYearSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                disabled>
                                                @for($y = date('Y'); $y >= 2010; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">From Month</label>
                                            <select id="revenueFromMonthSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                disabled>
                                                <option value="">All</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">To Year</label>
                                            <select id="revenueToYearSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                disabled>
                                                @for($y = date('Y'); $y >= 2010; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">To Month</label>
                                            <select id="revenueToMonthSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                disabled>
                                                <option value="">All</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        </div>
                                        <div id="revenueCompareRangeError" class="text-red-500 text-xs hidden">⚠ "From" must be earlier than "To"</div>
                                    </div>
                                    <!-- Range year+month fields -->
                                    <div class="flex flex-col gap-1 mb-2" id="revenueRangeFields" style="display:none;">
                                        <div class="flex flex-row flex-wrap gap-2">
                                            <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Start Year</label>
                                            <select id="revenueRangeStartYearSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                disabled>
                                                @for($y = date('Y'); $y >= 2010; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Start Month</label>
                                            <select id="revenueRangeStartMonthSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                disabled>
                                                <option value="">All</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">End Year</label>
                                            <select id="revenueRangeEndYearSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-sm"
                                                disabled>
                                                @for($y = date('Y'); $y >= 2010; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">End Month</label>
                                            <select id="revenueRangeEndMonthSelect"
                                                class="border border-gray-300 rounded-md px-2 py-1 bg-white text-xs"
                                                disabled>
                                                <option value="">All</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        </div>
                                        <div id="revenueRangeRangeError" class="text-red-500 text-xs hidden">⚠ "Start" must be earlier than or equal to "End"</div>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-card md:col-span-2 flex flex-col justify-between">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select id="revenueLocationSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Locations</option>
                                    <option value="Welisara">Welisara</option>
                                    <option value="Moratuwa">Moratuwa</option>
                                    <option value="Peradeniya">Peradeniya</option>
                                </select>

                                <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                <select id="revenueCourseSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Courses</option>
                                    @foreach(\App\Models\Course::all() as $course)
                                        <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button onclick="loadRevenueData()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid  mb-6 gap-4">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Revenue</h3>
                        <div style="height: 500px;">
                            <canvas id="revenueYearChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Outstanding</h3>
                        <div style="height: 300px;">
                            <canvas id="outstandingYearChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Revenues Tab Upload/Download -->
                <div class="flex gap-2 mb-4">
                    <button class="px-3 py-2 bg-green-600 text-white rounded" onclick="downloadRevenueTemplate()">Download
                        Revenue Excel Template</button>
                    <button class="px-3 py-2 bg-blue-600 text-white rounded"
                        onclick="showModal('revenueUploadModal')">Upload Revenue Data</button>
                </div>

                <div id="revenueUploadModal" style="display:none;"
                    class="fixed inset-0 flex items-center justify-center z-50 modal-overlay" aria-hidden="true">
                    <form id="revenueUploadForm" enctype="multipart/form-data" class="bg-white p-6 rounded shadow w-96"
                        method="POST" action="{{ route('bulk.revenue.upload') }}">
                        @csrf
                        <h3 class="mb-4 font-bold text-lg">Upload Revenue Excel</h3>
                        <input type="file" name="revenue_excel" accept=".xlsx,.xls,.csv" required class="mb-4">
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="hideModal('revenueUploadModal')"
                                class="px-3 py-1 bg-gray-400 text-white rounded">Cancel</button>
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Upload</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Outstanding Tab -->
            <div id="content-outstanding" class="tab-content">
                <div class="bg-white shadow-sm border-b mb-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-stretch">
                            <div class="filter-card">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select id="outstandingLocationSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Locations</option>
                                    <option value="Welisara">Welisara</option>
                                    <option value="Moratuwa">Moratuwa</option>
                                    <option value="Peradeniya">Peradeniya</option>
                                </select>
                            </div>

                            <div class="filter-card md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                                <select id="outstandingCourseSelect" multiple
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm">
                                    <option value="all">All Courses</option>
                                    @foreach(\App\Models\Course::all() as $course)
                                        <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button onclick="loadOutstandingTabData()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Outstanding by Location</h3>
                        <div style="height: 400px;">
                            <canvas id="outstandingTabChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Outstanding Summary</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Outstanding (Rs.)</th>
                                    </tr>
                                </thead>
                                <tbody id="outstandingSummaryBody" class="divide-y divide-gray-200">
                                    <!-- populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marketing Tab -->
            <div id="content-marketing" class="tab-content">
                <!-- Filter Controls -->

                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold mb-4">Marketing Survey Analysis</h3>
                    <div style="height: 400px;">
                        <canvas id="marketingSurveyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style nonce="{{ $cspNonce }}">
        .tab-active {
            background-color: #2563eb !important;
            color: white !important;
        }

        .tab-inactive {
            background-color: #e5e7eb !important;
            color: #374151 !important;
        }

        .tab-inactive:hover {
            background-color: #d1d5db !important;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .filter-card {
            background-color: white;
            padding: 0.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            width: 100%;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        select:disabled {
            background-color: #f3f4f6 !important;
            color: #9ca3af !important;
            cursor: not-allowed;
        }

        .modal-overlay {
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0);
            /* transparent so no darkening */
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            transition: opacity 120ms ease;
            padding: 1.25rem;

        }

        .modal-overlay.show {
            display: flex !important;
            opacity: 1;

        }

        .modal-overlay form {
            z-index: 60;
        }

        select[multiple] {
            min-height: 120px;
            padding: 0.5rem;
        }

        select[multiple] option {
            padding: 0.5rem;
            margin-bottom: 0.25rem;
        }

        select[multiple] option:checked {
            background: linear-gradient(#2563eb 0%, #2563eb 100%);
            background-color: #2563eb;
            color: white;
        }
    </style>

    <script nonce="{{ $cspNonce }}">
        let currentCharts = {};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function downloadStudentTemplate() {
            window.location.href = "{{ route('bulk.student.template') }}";
        }
        function downloadRevenueTemplate() {
            window.location.href = "{{ route('bulk.revenue.template') }}";
        }
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            document.getElementById('content-' + tabName)?.classList.add('active');

            document.querySelectorAll('[id^="tab-"]').forEach(button => {
                button.classList.remove('tab-active');
                button.classList.add('tab-inactive');
            });

            const activeButton = document.getElementById('tab-' + tabName);
            if (activeButton) {
                activeButton.classList.remove('tab-inactive');
                activeButton.classList.add('tab-active');
            }

            setTimeout(() => initializeChartsForTab(tabName), 100);
        }

        function showModal(id) {
            const modal = document.getElementById(id);
            const page = document.getElementById('pageContent');
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'flex';
                modal.classList.add('modal-overlay');
            }
            if (page) page.classList.add('blurred');
        }

        function hideModal(id) {
            const modal = document.getElementById(id);
            const page = document.getElementById('pageContent');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
            if (page) page.classList.remove('blurred');
        }

        function initializeChartsForTab(tabName) {
            switch (tabName) {
                case 'overview':
                    loadOverviewData();
                    break;
                case 'students':
                    loadStudentsData();
                    break;
                case 'revenues':
                    loadRevenueData();
                    loadOutstandingData();
                    break;
                case 'outstanding':
                    loadOutstandingTabData();
                    break;
                case 'marketing':
                    loadMarketingData();
                    break;
            }
        }

        // Load Overview Data
        async function loadOverviewData() {
            const params = {
                year: new Date().getFullYear(),
                location: 'all',
                course: 'all'
            };

            try {
                const response = await fetch(`/api/dashboard/overview?${new URLSearchParams(params)}`);
                const data = await response.json();

                document.getElementById('totalStudents').textContent = data.totalStudents;
                document.getElementById('yearlyRevenue').textContent = 'Rs. ' + data.yearlyRevenue;
                document.getElementById('outstanding').textContent = 'Rs. ' + data.outstanding;
                if (document.getElementById('outstandingCurrentYear')) {
                    document.getElementById('outstandingCurrentYear').textContent = 'Rs. ' + (data.outstandingCurrentYear ?? '0.00');
                }


                // Populate Revenue Summary Table
                const tbody = document.getElementById('revenueSummaryBody');
                tbody.innerHTML = '';

                if (data.locationSummary) {
                    let totalCurrentYear = 0;
                    let totalPreviousYear = 0;
                    let totalOutstanding = 0;

                    data.locationSummary.forEach(row => {

                        const current = parseFloat(row.current_year.toString().replace(/,/g, ""));
                        const previous = parseFloat(row.previous_year.toString().replace(/,/g, ""));
                        const outstanding = parseFloat(row.outstanding.toString().replace(/,/g, ""));

                        tbody.innerHTML += `
                                            <tr>
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900">${row.location}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">Rs. ${current.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">Rs. ${previous.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                                <td class="px-6 py-4 text-sm ${row.growth >= 0 ? 'text-green-600' : 'text-red-600'}">${row.growth >= 0 ? '+' : ''}${row.growth}%</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">Rs. ${outstanding.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            </tr>
                                        `;

                        totalCurrentYear += current;
                        totalPreviousYear += previous;
                        totalOutstanding += outstanding;
                    });

                    const totalGrowth = totalPreviousYear > 0
                        ? (((totalCurrentYear - totalPreviousYear) / totalPreviousYear) * 100).toFixed(1)
                        : 0;

                    // display totals WITH commas
                    document.getElementById('totalCurrentYear').textContent = 'Rs. ' + totalCurrentYear.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('totalPreviousYear').textContent = 'Rs. ' + totalPreviousYear.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('totalGrowth').textContent = (totalGrowth >= 0 ? '+' : '') + totalGrowth + '%';
                    document.getElementById('totalGrowth').className = 'px-6 py-3 text-sm ' + (totalGrowth >= 0 ? 'text-green-600' : 'text-red-600');
                    document.getElementById('totalOutstanding').textContent = 'Rs. ' + totalOutstanding.toLocaleString('en-US', { minimumFractionDigits: 2 });
                }

                // Fetch students by location and show in chart + numbers
                const studentsResponse = await fetch(`/api/dashboard/students-by-location?year=${params.year}`);
                const studentsData = await studentsResponse.json();

                // Show numbers below chart (add these spans in your blade if you want)
                // Example:
                // <div class="flex gap-4 mt-2 justify-center">
                //   <span id="studentsWelisara"></span>
                //   <span id="studentsMoratuwa"></span>
                //   <span id="studentsPeradeniya"></span>
                // </div>
                if (document.getElementById('studentsWelisara')) {
                    document.getElementById('studentsWelisara').textContent =
                        `Welisara: ${studentsData.find(d => d.institute_location === 'Welisara')?.count ?? 0}`;
                }
                if (document.getElementById('studentsMoratuwa')) {
                    document.getElementById('studentsMoratuwa').textContent =
                        `Moratuwa: ${studentsData.find(d => d.institute_location === 'Moratuwa')?.count ?? 0}`;
                }
                if (document.getElementById('studentsPeradeniya')) {
                    document.getElementById('studentsPeradeniya').textContent =
                        `Peradeniya: ${studentsData.find(d => d.institute_location === 'Peradeniya')?.count ?? 0}`;
                }

                // Draw chart
                const canvas = document.getElementById('studentsLocationChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.studentsLocation) {
                        currentCharts.studentsLocation.destroy();
                    }
                    currentCharts.studentsLocation = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: studentsData.map(d => d.institute_location),
                            datasets: [{
                                data: studentsData.map(d => d.count),
                                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }

                loadLocationBreakdown();
            } catch (error) {
                console.error('Error loading overview data:', error);
            }
        }

        // Load Location Breakdown
        async function loadLocationBreakdown() {
            const params = getFilterParams();

            try {
                const response = await fetch(`/api/dashboard/students-by-location?${new URLSearchParams(params)}`);
                const data = await response.json();

                const canvas = document.getElementById('studentsLocationChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.studentsLocation) {
                        currentCharts.studentsLocation.destroy();
                    }
                    // Always show all locations, even if count is zero
                    const allLocations = ['Welisara', 'Moratuwa', 'Peradeniya'];
                    const chartData = allLocations.map(loc => {
                        const found = data.find(d => d.institute_location === loc);
                        return found ? found.count : 0;
                    });
                    currentCharts.studentsLocation = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: allLocations,
                            datasets: [{
                                data: chartData,
                                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading location breakdown:', error);
            }
        }

        const MONTH_SHORT_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        function formatPeriodKey(year, month) {
            if (month) {
                return String(year) + '-' + String(parseInt(month, 10)).padStart(2, '0');
            }
            return String(year);
        }

        function formatPeriodLabel(year, month) {
            if (month) {
                return MONTH_SHORT_NAMES[parseInt(month, 10) - 1] + ' ' + year;
            }
            return String(year);
        }

        function rowPeriodKey(row) {
            return row.period || formatPeriodKey(row.year, row.month);
        }

        /** Build x-axis periods from the active Compare / Range / Year filter. */
        function buildChartPeriods(params) {
            if (params.compare && params.from_year && params.to_year) {
                return [
                    { period: formatPeriodKey(params.from_year, params.from_month), label: formatPeriodLabel(params.from_year, params.from_month) },
                    { period: formatPeriodKey(params.to_year, params.to_month), label: formatPeriodLabel(params.to_year, params.to_month) }
                ];
            }

            if (params.range && params.range_start_year && params.range_end_year) {
                const startY = parseInt(params.range_start_year, 10);
                const endY = parseInt(params.range_end_year, 10);
                const startM = params.range_start_month ? parseInt(params.range_start_month, 10) : null;
                const endM = params.range_end_month ? parseInt(params.range_end_month, 10) : null;
                const periods = [];

                if (startM || endM) {
                    let y = startY;
                    let m = startM || 1;
                    const lastM = endM || 12;
                    while (y < endY || (y === endY && m <= lastM)) {
                        periods.push({ period: formatPeriodKey(y, m), label: formatPeriodLabel(y, m) });
                        m += 1;
                        if (m > 12) {
                            m = 1;
                            y += 1;
                        }
                    }
                    return periods;
                }

                for (let y = startY; y <= endY; y++) {
                    periods.push({ period: String(y), label: String(y) });
                }
                return periods;
            }

            return [{
                period: formatPeriodKey(params.year, params.month),
                label: formatPeriodLabel(params.year, params.month)
            }];
        }

        // Load Students Data
        async function loadStudentsData() {
            if (window.validateStudentFilters && !window.validateStudentFilters()) {
                return; // block API call if date range is invalid
            }
            const params = getFilterParams();

            try {
                const response = await fetch(`/api/dashboard/students-data?${new URLSearchParams(params)}`);
                const data = await response.json();

                // Update location cards
                const welisara = data.filter(d => d.institute_location === 'Welisara').reduce((sum, d) => sum + (Number(d.count) || 0), 0);
                const Moratuwa = data.filter(d => d.institute_location === 'Moratuwa').reduce((sum, d) => sum + (Number(d.count) || 0), 0);
                const peradeniya = data.filter(d => d.institute_location === 'Peradeniya').reduce((sum, d) => sum + (Number(d.count) || 0), 0);

                document.getElementById('welisaraStudents').textContent = Math.round(welisara);
                document.getElementById('MoratuwaStudents').textContent = Math.round(Moratuwa);
                document.getElementById('peradeniyaStudents').textContent = Math.round(peradeniya);

                const periods = buildChartPeriods(params);
                const labels = periods.map(p => p.label);

                const locations = [...new Set(data.map(d => d.institute_location))].length ? [...new Set(data.map(d => d.institute_location))] : ['Welisara', 'Moratuwa', 'Peradeniya'];
                const coursesFromData = [...new Set(data.map(d => d.course_name))].filter(Boolean);

                // Determine selected course (courseSelect has course_id values) and map to displayed name
                const courseParam = params.course ?? null;
                let selectedCourseNames = [];
                if (!courseParam || courseParam === 'all') {
                    // no filter or "all" -> show all courses from payload
                    selectedCourseNames = [];
                } else {
                    // split incoming comma-separated ids and map to option text (course_name)
                    const ids = String(courseParam).split(',').map(s => s.trim()).filter(Boolean);
                    // if frontend included 'all' among selections, treat as no filter
                    if (ids.includes('all')) {
                        selectedCourseNames = [];
                    } else {
                        const courseSelectEl = document.getElementById('courseSelect');
                        const idToName = {};
                        if (courseSelectEl) {
                            [...courseSelectEl.options].forEach(o => {
                                idToName[o.value] = o.text;
                            });
                        }
                        selectedCourseNames = ids.map(id => idToName[id] ?? id);
                    }
                }

                // If course filter selected, only keep those course names; otherwise use courses from server payload
                const courses = selectedCourseNames.length ? selectedCourseNames : coursesFromData;

                const valueFor = (period, loc, courseName) => {
                    const found = courseName
                        ? data.find(d => rowPeriodKey(d) === period.period && d.institute_location === loc && d.course_name === courseName)
                        : data.find(d => rowPeriodKey(d) === period.period && d.institute_location === loc);
                    return Math.round(found ? Number(found.count) || 0 : 0);
                };

                // Fallback if no courses in payload -> per-location datasets
                if (courses.length === 0) {
                    const simpleDatasets = locations.map((loc, idx) => ({
                        label: loc,
                        data: periods.map(period => valueFor(period, loc, null)),
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'][idx % 3]
                    }));

                    const canvas = document.getElementById('chartCombined');
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        if (currentCharts.combined) currentCharts.combined.destroy();
                        currentCharts.combined = new Chart(ctx, {
                            type: 'bar',
                            data: { labels: labels, datasets: simpleDatasets },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'top' } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                            callback: value => Number.isInteger(value) ? value : ''
                                        }
                                    }
                                }
                            }
                        });
                    }
                    return;
                }

                const colors = [
                    '#3B82F6', '#60A5FA', '#93C5FD', // Welisara shades
                    '#10B981', '#34D399', '#6EE7B7', // Moratuwa shades
                    '#F59E0B', '#FBBF24', '#FCD34D'  // Peradeniya shades
                ];

                // Build combos only for courses we want to show and existing locations
                const combos = [];
                locations.forEach((loc) => {
                    courses.forEach((course) => combos.push({ loc, course }));
                });

                const datasets = combos.map((combo, idx) => ({
                    label: `${combo.course} (${combo.loc})`,
                    data: periods.map(period => valueFor(period, combo.loc, combo.course)),
                    backgroundColor: colors[idx % colors.length]
                }));

                const canvas = document.getElementById('chartCombined');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.combined) currentCharts.combined.destroy();
                    currentCharts.combined = new Chart(ctx, {
                        type: 'bar',
                        data: { labels: labels, datasets: datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        callback: value => Number.isInteger(value) ? value : ''
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading students data:', error);
            }
        }

        // Load Revenue Data
        async function loadRevenueData() {
            if (window.validateRevenueFilters && !window.validateRevenueFilters()) {
                return; // block API call if date range is invalid
            }
            const params = getRevenueFilterParams();

            try {
                const res = await fetch(`/api/dashboard/revenue-by-year-course?${new URLSearchParams(params)}`);
                const data = await res.json(); // [{year, location, course_name, revenue}, ...]

                const periods = buildChartPeriods(params);
                const labels = periods.map(p => p.label);

                // Get all locations and courses
                const locations = [...new Set(data.map(d => d.location))];
                const courses = [...new Set(data.map(d => d.course_name))];
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#6366F1', '#EC4899', '#22D3EE', '#A3E635'
                ];

                // Build all course+location combinations
                const combos = [];
                locations.forEach(loc => {
                    courses.forEach(course => {
                        combos.push({ loc, course });
                    });
                });

                const datasets = combos.map((combo, idx) => ({
                    label: `${combo.course} (${combo.loc})`,
                    data: periods.map(period => {
                        const found = data.find(d => rowPeriodKey(d) === period.period && d.location === combo.loc && d.course_name === combo.course);
                        return found ? found.revenue : 0;
                    }),
                    backgroundColor: colors[idx % colors.length]
                }));

                // Draw chart
                const canvas = document.getElementById('revenueYearChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.revenueYearCourse) {
                        currentCharts.revenueYearCourse.destroy();
                    }
                    currentCharts.revenueYearCourse = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } },
                            scales: {
                                x: { stacked: false },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return 'Rs. ' + (value / 1000000).toFixed(1) + 'M';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading revenue data:', error);
            }
        }

        // Load Future Projections
        async function loadFutureProjections() {
            try {
                const response = await fetch('/api/dashboard/future-projections');
                const data = await response.json();

                const actual = data.filter(d => d.type === 'actual');
                const projected = data.filter(d => d.type === 'projected');

                const canvas = document.getElementById('futureRevenueChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.futureRevenue) {
                        currentCharts.futureRevenue.destroy();
                    }
                    currentCharts.futureRevenue = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(d => d.label),
                            datasets: [
                                {
                                    label: 'Actual/Projected Revenue',
                                    data: data.map(d => d.revenue),
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                },
                                {
                                    label: 'Conservative Estimate',
                                    data: data.map(d => d.conservative || d.revenue),
                                    borderColor: '#10B981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return 'Rs. ' + (value / 1000).toFixed(0) + 'K';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading future projections:', error);
            }
        }

        async function loadOutstandingData() {
            const params = getRevenueFilterParams();

            try {
                const res = await fetch(`/api/dashboard/outstanding-by-year-course?${new URLSearchParams(params)}`);
                const data = await res.json(); // [{year, location, outstanding}, ...]

                // Get all years in data
                let years = [...new Set(data.map(d => d.year))].sort();

                // Filter years for compare/range
                if (params.compare && params.from_year && params.to_year) {
                    years = [parseInt(params.from_year), parseInt(params.to_year)].sort();
                } else if (params.range && params.range_start_year && params.range_end_year) {
                    const start = parseInt(params.range_start_year);
                    const end = parseInt(params.range_end_year);
                    years = [];
                    for (let y = start; y <= end; y++) years.push(y);
                }

                // Get all locations
                const locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
                const colors = ['#EF4444', '#6366F1', '#10B981'];

                // Sum outstanding for each location across selected years
                const locationOutstanding = locations.map(loc => {
                    return data
                        .filter(d => years.includes(d.year) && d.location === loc)
                        .reduce((sum, d) => sum + (d.outstanding || 0), 0);
                });

                // Draw pie chart
                const canvas = document.getElementById('outstandingYearChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.outstandingYearChart) {
                        currentCharts.outstandingYearChart.destroy();
                    }
                    currentCharts.outstandingYearChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: locations,
                            datasets: [{
                                data: locationOutstanding,
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading outstanding data:', error);
            }
        }

        async function loadMarketingData() {
            const year = new Date().getFullYear();
            try {
                const res = await fetch(`/api/dashboard/marketing-data?year=${year}`);
                const data = await res.json();

                const canvas = document.getElementById('marketingSurveyChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.marketingSurvey) {
                        currentCharts.marketingSurvey.destroy();
                    }
                    currentCharts.marketingSurvey = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Responses',
                                data: data.counts,
                                backgroundColor: [
                                    '#1877F2', '#E4405F', '#F59E0B', '#EF4444', '#6366F1', '#10B981', '#A3E635'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff',
                                borderRadius: {
                                    topLeft: 12,
                                    topRight: 12,
                                    bottomLeft: 0,
                                    bottomRight: 0
                                }
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading marketing data:', error);
            }
        }

        async function loadOutstandingTabData() {
            const params = getOutstandingFilterParams();
            try {
                const res = await fetch(`/api/dashboard/outstanding-by-year-course?${new URLSearchParams(params)}`);
                const data = await res.json(); // [{year, location, course_name, outstanding}, ...]

                // Build unique lists of courses and locations from payload
                const coursesSet = new Set();
                const locationsSet = new Set();
                data.forEach(item => {
                    const course = item.course_name || item.course || 'Unknown';
                    const loc = item.location || item.institute_location || 'Unknown';
                    coursesSet.add(course);
                    locationsSet.add(loc);
                });
                const courses = Array.from(coursesSet).sort();
                const locations = Array.from(locationsSet).length ? Array.from(locationsSet) : ['Welisara', 'Moratuwa', 'Peradeniya'];

                // Aggregate outstanding per location (for summary table) and prepare datasets per course by location
                const locationTotals = {};
                locations.forEach(loc => locationTotals[loc] = 0);

                // Color palette for courses (extendable)
                const colors = [
                    '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#6366F1', '#EC4899', '#22C1C3', '#A78BFA', '#F97316', '#06B6D4'
                ];

                // Build datasets: one dataset (bar) per course; x-axis = locations
                const datasets = courses.map((course, idx) => {
                    const vals = locations.map(loc => {
                        const matched = data.filter(d =>
                            (d.location === loc || d.institute_location === loc) &&
                            ((d.course_name === course) || (d.course === course))
                        );
                        const sum = matched.reduce((s, it) => s + (Number(it.outstanding) || 0), 0);
                        locationTotals[loc] += sum;
                        return Math.round(sum * 100) / 100;
                    });

                    const color = colors[idx % colors.length];
                    return {
                        label: course,
                        data: vals,
                        backgroundColor: color,
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 'flex'
                    };
                });

                // populate summary table (location totals) + total row
                const tbody = document.getElementById('outstandingSummaryBody');
                tbody.innerHTML = '';
                let grandTotal = 0;
                Object.keys(locationTotals).forEach(loc => {
                    const amt = locationTotals[loc];
                    grandTotal += amt;
                    tbody.innerHTML += `<tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">${loc}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">Rs. ${amt.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                    </tr>`;
                });

                // Add total row
                tbody.innerHTML += `<tr class="bg-gray-100 border-t-2 border-gray-300 font-bold">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Total</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">Rs. ${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                </tr>`;

                // Draw bar chart: x-axis = locations, datasets = courses (grouped bars)
                const canvas = document.getElementById('outstandingTabChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    if (currentCharts.outstandingTab) currentCharts.outstandingTab.destroy();
                    currentCharts.outstandingTab = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: locations,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const v = context.parsed.y ?? context.parsed ?? 0;
                                            return `${context.dataset.label}: Rs. ${Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    title: { display: true, text: 'Location' },
                                    stacked: false
                                },
                                y: {
                                    beginAtZero: true,
                                    title: { display: true, text: 'Outstanding (Rs.)' },
                                    ticks: {
                                        callback: function (value) {
                                            return 'Rs. ' + Number(value).toLocaleString();
                                        }
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                intersect: false
                            }
                        }
                    });
                }
            } catch (err) {
                console.error('Error loading outstanding tab data:', err);
            }
        }

        // Get filter parameters
        function getFilterParams() {
            const compareToggle = document.getElementById('compareToggle').checked;
            const rangeToggle = document.getElementById('rangeSelectorToggle').checked;
            const courseSelect = document.getElementById('courseSelect');
            const locationSelect = document.getElementById('locationSelect');

            // Get selected courses
            const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => opt.value);
            const courseParam = selectedCourses.length === 0 || selectedCourses.includes('all') ? 'all' : selectedCourses.join(',');

            const selectedLocations = Array.from(locationSelect.selectedOptions).map(opt => opt.value);
            const locationParam = selectedLocations.length === 0 || selectedLocations.includes('all') ? 'all' : selectedLocations.join(',');

            if (rangeToggle) {
                return {
                    range: true,
                    range_start_year: document.getElementById('rangeStartYearSelect').value,
                    range_start_month: document.getElementById('rangeStartMonthSelect').value,
                    range_end_year: document.getElementById('rangeEndYearSelect').value,
                    range_end_month: document.getElementById('rangeEndMonthSelect').value,
                    location: locationParam,
                    course: courseParam
                };
            } else if (compareToggle) {
                return {
                    compare: true,
                    from_year: document.getElementById('fromYearSelect').value,
                    from_month: document.getElementById('fromMonthSelect').value,
                    to_year: document.getElementById('toYearSelect').value,
                    to_month: document.getElementById('toMonthSelect').value,
                    location: locationParam,
                    course: courseParam
                };
            } else {
                return {
                    year: document.getElementById('yearSelect').value,
                    month: document.getElementById('studentMonthSelect').value,
                    date: document.getElementById('studentDaySelect').value,
                    location: locationParam,
                    course: courseParam
                };
            }
        }

        function getRevenueFilterParams() {
            const compareToggle = document.getElementById('revenueCompareToggle').checked;
            const rangeToggle = document.getElementById('revenueRangeSelectorToggle').checked;
            const revenueCourseSelect = document.getElementById('revenueCourseSelect');
            const revenueLocationSelect = document.getElementById('revenueLocationSelect');

            // Get selected courses
            const selectedCourses = Array.from(revenueCourseSelect.selectedOptions).map(opt => opt.value);
            const courseParam = selectedCourses.length === 0 || selectedCourses.includes('all') ? 'all' : selectedCourses.join(',');

            const selectedLocations = Array.from(revenueLocationSelect.selectedOptions).map(opt => opt.value);
            const locationParam = selectedLocations.length === 0 || selectedLocations.includes('all') ? 'all' : selectedLocations.join(',');

            if (rangeToggle) {
                return {
                    location: locationParam,
                    course: courseParam,
                    range: true,
                    range_start_year: document.getElementById('revenueRangeStartYearSelect').value,
                    range_start_month: document.getElementById('revenueRangeStartMonthSelect').value,
                    range_end_year: document.getElementById('revenueRangeEndYearSelect').value,
                    range_end_month: document.getElementById('revenueRangeEndMonthSelect').value
                };
            } else if (compareToggle) {
                return {
                    location: locationParam,
                    course: courseParam,
                    compare: true,
                    from_year: document.getElementById('revenueFromYearSelect').value,
                    from_month: document.getElementById('revenueFromMonthSelect').value,
                    to_year: document.getElementById('revenueToYearSelect').value,
                    to_month: document.getElementById('revenueToMonthSelect').value
                };
            } else {
                return {
                    year: document.getElementById('revenueYearSelect').value,
                    month: document.getElementById('revenueMonthSelect').value,
                    date: document.getElementById('revenueDaySelect').value,
                    location: locationParam,
                    course: courseParam
                };
            }
        }


        function getOutstandingFilterParams() {
            const locationSelect = document.getElementById('outstandingLocationSelect');
            const courseSelect = document.getElementById('outstandingCourseSelect');

            const selectedLocations = Array.from(locationSelect.selectedOptions).map(opt => opt.value);
            const locationParam = selectedLocations.length === 0 || selectedLocations.includes('all') ? 'all' : selectedLocations.join(',');

            const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => opt.value);
            const courseParam = selectedCourses.length === 0 || selectedCourses.includes('all') ? 'all' : selectedCourses.join(',');

            return {
                year: new Date().getFullYear(),
                location: locationParam,
                course: courseParam
            };
        }


        document.addEventListener('DOMContentLoaded', function () {
            // Attach event listeners to tab buttons
            document.querySelectorAll('[data-tab]').forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    showTab(tabName);
                });
            });

            const compareToggle = document.getElementById('compareToggle');
            const rangeToggle = document.getElementById('rangeSelectorToggle');
            const yearSelect = document.getElementById('yearSelect');
            const studentMonthSelect = document.getElementById('studentMonthSelect');
            const studentDaySelect = document.getElementById('studentDaySelect');
            const compareFields = document.getElementById('compareFields');
            const rangeFields = document.getElementById('rangeFields');
            const fromYearSelect = document.getElementById('fromYearSelect');
            const fromMonthSelect = document.getElementById('fromMonthSelect');
            const toYearSelect = document.getElementById('toYearSelect');
            const toMonthSelect = document.getElementById('toMonthSelect');
            const rangeStartYearSelect = document.getElementById('rangeStartYearSelect');
            const rangeStartMonthSelect = document.getElementById('rangeStartMonthSelect');
            const rangeEndYearSelect = document.getElementById('rangeEndYearSelect');
            const rangeEndMonthSelect = document.getElementById('rangeEndMonthSelect');
            const compareRangeError = document.getElementById('compareRangeError');
            const rangeRangeError = document.getElementById('rangeRangeError');

            const revenueCompareToggle = document.getElementById('revenueCompareToggle');
            const revenueRangeToggle = document.getElementById('revenueRangeSelectorToggle');
            const revenueYearSelect = document.getElementById('revenueYearSelect');
            const revenueMonthSelect = document.getElementById('revenueMonthSelect');
            const revenueDaySelect = document.getElementById('revenueDaySelect');
            const revenueCompareFields = document.getElementById('revenueCompareFields');
            const revenueRangeFields = document.getElementById('revenueRangeFields');
            const revenueFromYearSelect = document.getElementById('revenueFromYearSelect');
            const revenueFromMonthSelect = document.getElementById('revenueFromMonthSelect');
            const revenueToYearSelect = document.getElementById('revenueToYearSelect');
            const revenueToMonthSelect = document.getElementById('revenueToMonthSelect');
            const revenueRangeStartYearSelect = document.getElementById('revenueRangeStartYearSelect');
            const revenueRangeStartMonthSelect = document.getElementById('revenueRangeStartMonthSelect');
            const revenueRangeEndYearSelect = document.getElementById('revenueRangeEndYearSelect');
            const revenueRangeEndMonthSelect = document.getElementById('revenueRangeEndMonthSelect');
            const revenueCompareRangeError = document.getElementById('revenueCompareRangeError');
            const revenueRangeRangeError = document.getElementById('revenueRangeRangeError');

            /**
             * Validate that (fromYear, fromMonth) is strictly before (toYear, toMonth).
             * Month values may be empty string (treat as "any").
             * For Compare: from < to strictly.
             * For Range: from <= to, but not exactly equal (same year & same/no month).
             */
            function periodValue(year, month) {
                return parseInt(year) * 100 + (month ? parseInt(month) : 0);
            }

            function isValidComparePeriod(fy, fm, ty, tm) {
                if (!fy || !ty) return true; // incomplete selection, skip
                // "All" months: from-side defaults to Jan (1), to-side to Dec (12)
                const fv = parseInt(fy) * 100 + (fm ? parseInt(fm) : 1);
                const tv = parseInt(ty) * 100 + (tm ? parseInt(tm) : 12);
                return fv < tv;
            }

            function isValidRangePeriod(sy, sm, ey, em) {
                if (!sy || !ey) return true;
                // "All" months: start-side defaults to Jan (1), end-side to Dec (12)
                const sv = parseInt(sy) * 100 + (sm ? parseInt(sm) : 1);
                const ev = parseInt(ey) * 100 + (em ? parseInt(em) : 12);
                return sv < ev;
            }

            function validateStudentCompare() {
                const valid = isValidComparePeriod(
                    fromYearSelect.value, fromMonthSelect.value,
                    toYearSelect.value, toMonthSelect.value
                );
                compareRangeError.classList.toggle('hidden', valid);
                return valid;
            }

            function validateStudentRange() {
                const valid = isValidRangePeriod(
                    rangeStartYearSelect.value, rangeStartMonthSelect.value,
                    rangeEndYearSelect.value, rangeEndMonthSelect.value
                );
                rangeRangeError.classList.toggle('hidden', valid);
                return valid;
            }

            function validateRevenueCompare() {
                const valid = isValidComparePeriod(
                    revenueFromYearSelect.value, revenueFromMonthSelect.value,
                    revenueToYearSelect.value, revenueToMonthSelect.value
                );
                revenueCompareRangeError.classList.toggle('hidden', valid);
                return valid;
            }

            function validateRevenueRange() {
                const valid = isValidRangePeriod(
                    revenueRangeStartYearSelect.value, revenueRangeStartMonthSelect.value,
                    revenueRangeEndYearSelect.value, revenueRangeEndMonthSelect.value
                );
                revenueRangeRangeError.classList.toggle('hidden', valid);
                return valid;
            }

            // Expose validators so loadStudentsData / loadRevenueData can call them
            window.validateStudentFilters = function() {
                if (compareToggle.checked) return validateStudentCompare();
                if (rangeToggle.checked) return validateStudentRange();
                return true;
            };
            window.validateRevenueFilters = function() {
                if (revenueCompareToggle.checked) return validateRevenueCompare();
                if (revenueRangeToggle.checked) return validateRevenueRange();
                return true;
            };

            function updateSelectors() {
                if (rangeToggle.checked) {
                    yearSelect.disabled = true;
                    studentMonthSelect.disabled = true;
                    studentDaySelect.disabled = true;
                    compareFields.style.display = 'none';
                    rangeFields.style.display = 'flex';
                    rangeStartYearSelect.disabled = false;
                    rangeStartMonthSelect.disabled = false;
                    rangeEndYearSelect.disabled = false;
                    rangeEndMonthSelect.disabled = false;
                    fromYearSelect.disabled = true;
                    fromMonthSelect.disabled = true;
                    toYearSelect.disabled = true;
                    toMonthSelect.disabled = true;
                    compareRangeError.classList.add('hidden');
                    validateStudentRange();
                } else if (compareToggle.checked) {
                    yearSelect.disabled = true;
                    studentMonthSelect.disabled = true;
                    studentDaySelect.disabled = true;
                    compareFields.style.display = 'flex';
                    rangeFields.style.display = 'none';
                    fromYearSelect.disabled = false;
                    fromMonthSelect.disabled = false;
                    toYearSelect.disabled = false;
                    toMonthSelect.disabled = false;
                    rangeStartYearSelect.disabled = true;
                    rangeStartMonthSelect.disabled = true;
                    rangeEndYearSelect.disabled = true;
                    rangeEndMonthSelect.disabled = true;
                    rangeRangeError.classList.add('hidden');
                    validateStudentCompare();
                } else {
                    yearSelect.disabled = false;
                    studentMonthSelect.disabled = false;
                    studentDaySelect.disabled = false;
                    compareFields.style.display = 'none';
                    rangeFields.style.display = 'none';
                    fromYearSelect.disabled = true;
                    fromMonthSelect.disabled = true;
                    toYearSelect.disabled = true;
                    toMonthSelect.disabled = true;
                    rangeStartYearSelect.disabled = true;
                    rangeStartMonthSelect.disabled = true;
                    rangeEndYearSelect.disabled = true;
                    rangeEndMonthSelect.disabled = true;
                    compareRangeError.classList.add('hidden');
                    rangeRangeError.classList.add('hidden');
                }
            }

            compareToggle.addEventListener('change', function () {
                if (compareToggle.checked) rangeToggle.checked = false;
                updateSelectors();
            });
            rangeToggle.addEventListener('change', function () {
                if (rangeToggle.checked) compareToggle.checked = false;
                updateSelectors();
            });

            // Re-validate on any year/month change
            [fromYearSelect, fromMonthSelect, toYearSelect, toMonthSelect].forEach(el => {
                el.addEventListener('change', validateStudentCompare);
            });
            [rangeStartYearSelect, rangeStartMonthSelect, rangeEndYearSelect, rangeEndMonthSelect].forEach(el => {
                el.addEventListener('change', validateStudentRange);
            });

            updateSelectors();

            function updateRevenueSelectors() {
                if (revenueRangeToggle.checked) {
                    revenueYearSelect.disabled = true;
                    revenueMonthSelect.disabled = true;
                    revenueDaySelect.disabled = true;
                    revenueCompareFields.style.display = 'none';
                    revenueRangeFields.style.display = 'flex';
                    revenueRangeStartYearSelect.disabled = false;
                    revenueRangeStartMonthSelect.disabled = false;
                    revenueRangeEndYearSelect.disabled = false;
                    revenueRangeEndMonthSelect.disabled = false;
                    revenueFromYearSelect.disabled = true;
                    revenueFromMonthSelect.disabled = true;
                    revenueToYearSelect.disabled = true;
                    revenueToMonthSelect.disabled = true;
                    revenueCompareRangeError.classList.add('hidden');
                    validateRevenueRange();
                } else if (revenueCompareToggle.checked) {
                    revenueYearSelect.disabled = true;
                    revenueMonthSelect.disabled = true;
                    revenueDaySelect.disabled = true;
                    revenueCompareFields.style.display = 'flex';
                    revenueRangeFields.style.display = 'none';
                    revenueFromYearSelect.disabled = false;
                    revenueFromMonthSelect.disabled = false;
                    revenueToYearSelect.disabled = false;
                    revenueToMonthSelect.disabled = false;
                    revenueRangeStartYearSelect.disabled = true;
                    revenueRangeStartMonthSelect.disabled = true;
                    revenueRangeEndYearSelect.disabled = true;
                    revenueRangeEndMonthSelect.disabled = true;
                    revenueRangeRangeError.classList.add('hidden');
                    validateRevenueCompare();
                } else {
                    revenueYearSelect.disabled = false;
                    revenueMonthSelect.disabled = false;
                    revenueDaySelect.disabled = false;
                    revenueCompareFields.style.display = 'none';
                    revenueRangeFields.style.display = 'none';
                    revenueFromYearSelect.disabled = true;
                    revenueFromMonthSelect.disabled = true;
                    revenueToYearSelect.disabled = true;
                    revenueToMonthSelect.disabled = true;
                    revenueRangeStartYearSelect.disabled = true;
                    revenueRangeStartMonthSelect.disabled = true;
                    revenueRangeEndYearSelect.disabled = true;
                    revenueRangeEndMonthSelect.disabled = true;
                    revenueCompareRangeError.classList.add('hidden');
                    revenueRangeRangeError.classList.add('hidden');
                }
            }

            revenueCompareToggle.addEventListener('change', function () {
                if (revenueCompareToggle.checked) revenueRangeToggle.checked = false;
                updateRevenueSelectors();
            });
            revenueRangeToggle.addEventListener('change', function () {
                if (revenueRangeToggle.checked) revenueCompareToggle.checked = false;
                updateRevenueSelectors();
            });

            // Re-validate on any revenue year/month change
            [revenueFromYearSelect, revenueFromMonthSelect, revenueToYearSelect, revenueToMonthSelect].forEach(el => {
                el.addEventListener('change', validateRevenueCompare);
            });
            [revenueRangeStartYearSelect, revenueRangeStartMonthSelect, revenueRangeEndYearSelect, revenueRangeEndMonthSelect].forEach(el => {
                el.addEventListener('change', validateRevenueRange);
            });

            updateRevenueSelectors();

            // wire month/year change events to populate days and enable/disable day select
            studentMonthSelect.addEventListener('change', () => {
                populateDays('studentDaySelect', 'yearSelect', 'studentMonthSelect');
                // update selectors in case month cleared
                updateSelectors();
            });
            yearSelect.addEventListener('change', () => {
                populateDays('studentDaySelect', 'yearSelect', 'studentMonthSelect');
                syncMonthToYear('studentMonthSelect', 'yearSelect');
            });

            revenueMonthSelect.addEventListener('change', () => {
                populateDays('revenueDaySelect', 'revenueYearSelect', 'revenueMonthSelect');
                updateRevenueSelectors();
            });
            revenueYearSelect.addEventListener('change', () => {
                populateDays('revenueDaySelect', 'revenueYearSelect', 'revenueMonthSelect');
                syncMonthToYear('revenueMonthSelect', 'revenueYearSelect');
            });

            // Sync future-month disabling for all compare/range year↔month pairs
            const yearMonthPairs = [
                ['fromYearSelect',               'fromMonthSelect'],
                ['toYearSelect',                 'toMonthSelect'],
                ['rangeStartYearSelect',         'rangeStartMonthSelect'],
                ['rangeEndYearSelect',           'rangeEndMonthSelect'],
                ['revenueFromYearSelect',        'revenueFromMonthSelect'],
                ['revenueToYearSelect',          'revenueToMonthSelect'],
                ['revenueRangeStartYearSelect',  'revenueRangeStartMonthSelect'],
                ['revenueRangeEndYearSelect',    'revenueRangeEndMonthSelect'],
            ];
            yearMonthPairs.forEach(([yId, mId]) => {
                const yEl = document.getElementById(yId);
                const mEl = document.getElementById(mId);
                if (yEl && mEl) {
                    yEl.addEventListener('change', () => updateMonthOptions(mEl, yEl.value));
                }
            });

            // populate on load (will disable day selects if no month)
            populateDays('studentDaySelect', 'yearSelect', 'studentMonthSelect');
            populateDays('revenueDaySelect', 'revenueYearSelect', 'revenueMonthSelect');

            // Disable future months on load for every month select
            syncMonthToYear('studentMonthSelect', 'yearSelect');
            syncMonthToYear('revenueMonthSelect', 'revenueYearSelect');
            yearMonthPairs.forEach(([yId, mId]) => syncMonthToYear(mId, yId));

        });


        function populateDays(daySelectId, yearSelectId, monthSelectId) {
            const daySelect = document.getElementById(daySelectId);
            const yearEl = document.getElementById(yearSelectId);
            const monthEl = document.getElementById(monthSelectId);
            if (!daySelect || !yearEl || !monthEl) return;

            const year = yearEl.value || new Date().getFullYear();
            const month = monthEl.value;

            // Reset options
            daySelect.innerHTML = '<option value="">All Days</option>';

            if (month) {
                // month is in "MM" format; JS Date expects month index for next month, so pass parseInt(month)
                const daysInMonth = new Date(year, parseInt(month), 0).getDate();
                for (let d = 1; d <= daysInMonth; d++) {
                    const dayStr = d.toString().padStart(2, '0');
                    daySelect.innerHTML += `<option value="${dayStr}">${dayStr}</option>`;
                }
                daySelect.disabled = false;
            } else {
                // No month selected -> disable day selector
                daySelect.disabled = true;
            }
        }

        /**
         * Disable future month options in a month <select> based on the selected year.
         * If year === current year: disable months > current month.
         * If year > current year: disable all months.
         * If year < current year: enable all months.
         * Also resets the selected value if it is now disabled.
         */
        function updateMonthOptions(monthSelectEl, year) {
            if (!monthSelectEl) return;
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth() + 1; // 1–12
            const selectedYear = parseInt(year) || currentYear;

            Array.from(monthSelectEl.options).forEach(opt => {
                if (!opt.value) return; // skip blank "All Months" option
                const m = parseInt(opt.value);
                const isFuture = (selectedYear > currentYear) ||
                                 (selectedYear === currentYear && m > currentMonth);
                opt.disabled = isFuture;
                opt.style.color = isFuture ? '#9CA3AF' : '';
            });

            // If the currently-selected month is now disabled, reset to blank
            const sel = monthSelectEl.selectedIndex;
            if (sel > 0 && monthSelectEl.options[sel] && monthSelectEl.options[sel].disabled) {
                monthSelectEl.value = '';
            }
        }

        /** Convenience: update by element id and a companion year-select id */
        function syncMonthToYear(monthSelectId, yearSelectId) {
            const m = document.getElementById(monthSelectId);
            const y = document.getElementById(yearSelectId);
            if (m && y) updateMonthOptions(m, y.value);
        }

        document.addEventListener('DOMContentLoaded', function () {
            function enableClickMultiSelect(selectId) {
                const sel = document.getElementById(selectId);
                if (!sel) return;
                sel.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    const opt = e.target;
                    if (!opt || opt.tagName !== 'OPTION') return;

                    // Toggle clicked option
                    opt.selected = !opt.selected;

                    // If "all" clicked -> select/deselect all options to match it
                    if (opt.value === 'all') {
                        const allSelected = opt.selected;
                        Array.from(sel.options).forEach(o => o.selected = allSelected);
                    } else {
                        // If any real option clicked, ensure "all" is unset
                        const allOpt = Array.from(sel.options).find(o => o.value === 'all');
                        if (allOpt && allOpt.selected) allOpt.selected = false;
                    }

                    // keep focus and notify change listeners
                    sel.focus();
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }

            enableClickMultiSelect('courseSelect');
            enableClickMultiSelect('revenueCourseSelect');
            enableClickMultiSelect('locationSelect');
            enableClickMultiSelect('revenueLocationSelect');
            enableClickMultiSelect('outstandingLocationSelect');
            enableClickMultiSelect('outstandingCourseSelect');
        });

        document.addEventListener('DOMContentLoaded', function () {
            function bindUpload(formId, modalId, onSuccess) {
                const form = document.getElementById(formId);
                if (!form) return;
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData,
                            credentials: 'same-origin'
                        });

                        // Handle validation errors (422) with useful messages
                        if (res.status === 422) {
                            const body = await res.json().catch(() => null);
                            const errs = (body && body.errors) ? body.errors : null;
                            if (errs) {
                                const messages = [];
                                Object.values(errs).forEach(arr => {
                                    if (Array.isArray(arr)) messages.push(...arr);
                                    else messages.push(arr);
                                });
                                throw new Error(messages.join(' ; ') || body.message || 'Validation failed');
                            }
                            throw new Error(body?.message || 'Validation failed');
                        }

                        if (!res.ok) {
                            // try to parse JSON error message, otherwise text/html
                            const contentType = res.headers.get('content-type') || '';
                            if (contentType.includes('application/json')) {
                                const body = await res.json().catch(() => null);
                                throw new Error(body?.message || JSON.stringify(body) || `Upload failed (${res.status})`);
                            } else {
                                const text = await res.text();
                                // If HTML returned, give a concise hint and log details to console
                                console.error('Server response (non-JSON):', text);
                                throw new Error(`Server error (${res.status}). See console/network tab for details.`);
                            }
                        }

                        const ct = res.headers.get('content-type') || '';
                        if (!ct.includes('application/json')) {
                            const text = await res.text();
                            throw new Error(text || 'Server did not return JSON');
                        }

                        const json = await res.json();
                        if (json.success) {
                            if (modalId) document.getElementById(modalId).style.display = 'none';
                            if (onSuccess) onSuccess(json);
                            alert('Uploaded ' + (json.inserted ?? 0) + ' rows.');
                        } else {
                            throw new Error(json.message || 'Upload failed');
                        }
                    } catch (err) {
                        console.error('Upload error details:', err);
                        alert('Upload error: ' + (err.message || err));
                    }
                });
            }

            bindUpload('studentUploadForm', 'studentUploadModal', () => loadStudentsData());
            bindUpload('revenueUploadForm', 'revenueUploadModal', () => { loadRevenueData(); loadOutstandingData(); });
        });

        // Download exports (actual uploaded table data)
        function downloadStudentExport() {
            window.location.href = "{{ route('bulk.student.export') }}";
        }
        function downloadRevenueExport() {
            window.location.href = "{{ route('bulk.revenue.export') }}";
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            showTab('overview');
        });
    </script>
@endsection