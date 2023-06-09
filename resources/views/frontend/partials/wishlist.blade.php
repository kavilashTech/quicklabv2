





@if(!empty(Auth::user()) && Auth::user()->user_type == 'partner')

    
    <a href="javascript::void(0)" class="d-flex align-items-center text-reset" style="cursor: default">
    @else
    <a href="{{ route('wishlists.index') }}" class="d-flex align-items-center text-reset">
    
@endif
    <i class="la la-heart-o la-2x opacity-80" style="font-size: 28px"></i>
    <span class="flex-grow-1 ml-1">
        @if(Auth::check())
            <span class="badge badge-primary badge-inline badge-pill">{{ count(Auth::user()->wishlists)}}</span>
        @else
            <span class="badge badge-primary badge-inline badge-pill">0</span>
        @endif
        <span class="nav-box-text d-none d-xl-block opacity-70">{{translate('Wishlist')}}</span>
    </span>
</a>
