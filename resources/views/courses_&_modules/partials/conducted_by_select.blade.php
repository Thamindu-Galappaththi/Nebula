@php
    $p = $prefix ?? '';
    $req = !empty($required);
    $options = $conductedByOptions ?? \App\Models\ConductedByOption::defaults();
@endphp
<div class="conducted-by-field">
    <select class="form-select conducted-by-select" id="{{ $p }}conducted_by" name="conducted_by" @if($req) required @endif>
        <option selected disabled value="">Select who conducts</option>
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
        <option value="Other">Other</option>
    </select>
    <input type="text" class="form-control mt-2 other-conducted-by" id="{{ $p }}other_conducted_by" name="other_conducted_by" placeholder="Enter new conducted by name" hidden>
</div>
