@props([
    'paginator',
    'showPerPage' => false,
    'allowedSizes' => [10, 20, 50, 100, 500],
    'class' => 'card-footer bg-white py-3 border-top',
])

@if(isset($paginator) && $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="{{ $class }} d-flex flex-wrap justify-content-between align-items-center gap-2">
        {{-- Left: Entry Count Summary --}}
        <div class="small text-secondary">
            Showing <strong>{{ $paginator->firstItem() ?? 0 }}</strong> to <strong>{{ $paginator->lastItem() ?? 0 }}</strong> of <strong>{{ $paginator->total() }}</strong> entries
        </div>

        {{-- Right: Pagination Links --}}
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if($showPerPage)
                <form method="get" class="d-inline-flex align-items-center gap-1 m-0 p-0">
                    @foreach(request()->except(['per_page', 'page']) as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $subK => $subV)
                                @if(!is_null($subV))
                                    <input type="hidden" name="{{ $k }}[{{ $subK }}]" value="{{ $subV }}">
                                @endif
                            @endforeach
                        @elseif(!is_null($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label class="small text-muted mb-0 me-1 text-nowrap">Show:</label>
                    <select name="per_page" class="form-select form-select-sm" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                        @foreach($allowedSizes as $size)
                            <option value="{{ $size }}" @selected($paginator->perPage() == $size)>{{ $size }} per page</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="d-inline-block [&_.pagination]:mb-0">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
@endif
