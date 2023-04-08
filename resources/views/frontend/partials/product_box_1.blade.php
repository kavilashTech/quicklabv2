<div class="aiz-card-box border border-light rounded hov-shadow-md mt-1 mb-2 has-transition bg-white">
    @if (discount_in_percentage($product) > 0)
        <span class="badge-custom">{{ translate('OFF') }}<span
                class="box ml-1 mr-0">&nbsp;{{ discount_in_percentage($product) }}%</span></span>
    @endif
    <div class="position-relative">
        @php
            $product_url = route('product', $product->slug);
            if ($product->auction_product == 1) {
                $product_url = route('auction-product', $product->slug);
            }
        @endphp
        <a href="{{ $product_url }}" class="d-block">
            <img class="img-fit lazyload mx-auto h-140px h-md-210px"
                src="{{ asset('assets/img/placeholder.jpg') }}"
                data-src="{{ uploaded_asset($product->thumbnail_img) }}" alt="{{ $product->getTranslation('name') }}"
                onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">
        </a>
        @if ($product->wholesale_product)
            <span class="absolute-bottom-left fs-11 text-white fw-600 px-2 lh-1-8" style="background-color: #455a64">
                {{ translate('Wholesale') }}
            </span>
        @endif
        @if (isset(Auth::user()->user_type) && Auth::user()->user_type == 'partner')
        @else
            <div class="absolute-top-right aiz-p-hov-icon">
                <a href="javascript:void(0)" onclick="addToWishList({{ $product->id }})" data-toggle="tooltip"
                    data-title="{{ translate('Add to wishlist') }}" data-placement="left">
                    <i class="la la-heart-o la-2x pt-1"></i>
                </a>
                <!-- <a href="javascript:void(0)" onclick="addToCompare({{ $product->id }})" data-toggle="tooltip" data-title="{{ translate('Add to compare') }}" data-placement="left">
                    <i class="las la-sync"></i>
                </a> -->
                <a href="javascript:void(0)" onclick="showAddToCartModal({{ $product->id }})" data-toggle="tooltip"
                    data-title="{{ translate('Add to cart') }}" data-placement="left">
                    <i class="las la-shopping-cart la-2x pt-1"></i>
                </a>
            </div>
        @endif
    </div>
    <div class="p-ms-3 text-left">
        <div class="fs-15">
            <del class="fw-600 opacity-50 mr-1 px-2">
                @if (home_base_price($product) != home_discounted_base_price($product))
                    {{ home_base_price($product) }}
                @endif
            </del>
            <span class="fw-700 text-primary float-right">{{ home_discounted_base_price($product) }}</span>
        </div>
        <div class="rating rating-sm mt-1 px-2">
            {{ renderStarRating($product->rating) }}
        </div>
        @if (isset(Auth::user()->user_type) && Auth::user()->user_type == 'partner')
            <div class="rounded px-2 mt-2">
                @if (isset($product->stocks[0]) && isset($product->stocks[0]->wholesale_price))
                    {{ translate(' Franchisee Price') }}:
                    <span class="fw-700">{{ format_price($product->stocks[0]->wholesale_price) }}</span>
                @endif
            </div>
        @endif
        <h3 class="fw-600 fs-13 text-truncate-2 lh-1-4 mb-0 h-35px px-2">
            <a href="{{ $product_url }}" class="d-block text-reset">{{ $product->getTranslation('name') }}</a>
        </h3>
        @if (addon_is_activated('club_point'))
            <!-- <div class="rounded px-2 mt-2 bg-soft-primary border-soft-primary border"> -->
            <div class="rounded px-2 mt-2">
                {{ translate('Club Point') }}:
                <span class="fw-700">{{ $product->earn_point }}</span>
            </div>
        @endif
    </div>
</div>
