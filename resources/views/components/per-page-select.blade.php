@props([
    'paginator' => null,
    'allowedSizes' => [10, 20, 50, 100, 500],
])

@php
    $currentSize = $paginator ? $paginator->perPage() : (int) request('per_page', 10);
@endphp

<div class="d-inline-flex align-items-center gap-1">
    <label class="small text-muted mb-0 text-nowrap" style="font-size: 0.8125rem;">Show:</label>
    <select name="per_page" class="form-select form-select-sm py-0 ps-2 pe-4" style="font-size: 0.8125rem; height: 31px; width: auto;" onchange="this.form.submit()">
        @foreach($allowedSizes as $size)
            <option value="{{ $size }}" @selected($currentSize == $size)>{{ $size }} per page</option>
        @endforeach
    </select>
</div>
