@extends('inc.app')

@section('title', 'NEBULA | Reporting')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Reporting</h1>
                <p class="text-muted mb-0">Generate operational reports for students, courses, attendance, finance, and modules.</p>
            </div>
        </div>

        <div class="row g-4">
            @php
                $reports = [
                    ['title' => 'Student Enrollment', 'route' => 'reporting.enrollment', 'fields' => ['dates', 'location', 'course']],
                    ['title' => 'Course Performance', 'route' => 'reporting.performance', 'fields' => ['location', 'course', 'semester']],
                    ['title' => 'Attendance', 'route' => 'reporting.attendance', 'fields' => ['dates', 'location', 'course', 'semester']],
                    ['title' => 'Financial', 'route' => 'reporting.financial', 'fields' => ['dates', 'location', 'course']],
                    ['title' => 'Module Assignment', 'route' => 'reporting.module', 'fields' => ['location', 'course', 'semester']],
                ];
            @endphp

            @foreach ($reports as $report)
                <div class="col-12 col-xl-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">{{ $report['title'] }}</h2>
                            <form method="POST" action="{{ route($report['route']) }}" target="_blank">
                                @csrf
                                @if (in_array('dates', $report['fields']))
                                    <div class="row g-3 mb-3">
                                        <div class="col-sm-6">
                                            <label class="form-label" for="{{ $report['route'] }}-start">Start date</label>
                                            <input class="form-control" id="{{ $report['route'] }}-start" type="date" name="start_date">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label" for="{{ $report['route'] }}-end">End date</label>
                                            <input class="form-control" id="{{ $report['route'] }}-end" type="date" name="end_date">
                                        </div>
                                    </div>
                                @endif

                                <div class="row g-3 mb-3">
                                    @if (in_array('location', $report['fields']))
                                        <div class="col-sm-6">
                                            <label class="form-label" for="{{ $report['route'] }}-location">Location</label>
                                            <select class="form-select" id="{{ $report['route'] }}-location" name="location">
                                                <option value="">All locations</option>
                                                <option value="Welisara">Welisara</option>
                                                <option value="Moratuwa">Moratuwa</option>
                                                <option value="Peradeniya">Peradeniya</option>
                                            </select>
                                        </div>
                                    @endif
                                    @if (in_array('course', $report['fields']))
                                        <div class="col-sm-6">
                                            <label class="form-label" for="{{ $report['route'] }}-course">Course ID</label>
                                            <input class="form-control" id="{{ $report['route'] }}-course" type="number" min="1" name="course_id" placeholder="All courses">
                                        </div>
                                    @endif
                                    @if (in_array('semester', $report['fields']))
                                        <div class="col-sm-6">
                                            <label class="form-label" for="{{ $report['route'] }}-semester">Semester</label>
                                            <input class="form-control" id="{{ $report['route'] }}-semester" type="text" name="semester" maxlength="50" placeholder="All semesters">
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit">Generate report</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
