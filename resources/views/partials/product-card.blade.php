@use('App\Support\Money')
<article class="card reveal">
    <a href="{{ route('products.show', $product->slug) }}" class="card-img">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <div class="ph">{{ mb_substr($product->name, 0, 1) }}</div>
        @endif
        @if($product->is_best_seller) <span class="tag">★ Best Seller</span> @endif
        @if($product->stock < 1) <span class="tag tag-soldout">Habis</span> @endif
    </a>
    <div class="card-body">
        <span class="card-cat">{{ $product->category?->name ?? 'Dessert' }}</span>
        <h3 class="card-title"><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>
        <div class="card-foot">
            <span class="price">{{ Money::rp($product->price) }}</span>
            <form class="js-add-cart" method="POST" action="{{ route('cart.add') }}" data-cart-url="{{ route('cart.index') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-primary btn-sm" @disabled($product->stock < 1)>+ Keranjang</button>
            </form>
        </div>
    </div>
</article>
