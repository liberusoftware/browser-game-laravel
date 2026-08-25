<section aria-labelledby="browser-game-commerce-heading">
    <h2 id="browser-game-commerce-heading">Commerce</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    @if($error)<p role="alert">{{ $error }}</p>@endif
    @forelse($products as $product)
        <article wire:key="product-{{ $product->id }}">
            <h3>{{ $product->name }}</h3>
            <p>{{ $product->price }} {{ $product->currency_code }}</p>
            <button type="button" wire:click="checkout('{{ $product->id }}')" wire:loading.attr="disabled">Buy</button>
        </article>
    @empty
        <p role="status">No products are available.</p>
    @endforelse
    <h3>Orders</h3>
    @forelse($orders as $order)
        <article wire:key="order-{{ $order->id }}"><p>{{ $order->status }} · {{ $order->total }} {{ $order->currency_code }}</p>@if($order->status === 'pending')<button type="button" wire:click="complete('{{ $order->id }}')">Complete order</button>@elseif($order->status === 'completed')<button type="button" wire:click="refund('{{ $order->id }}')">Refund order</button>@endif</article>
    @empty
        <p role="status">No orders are available.</p>
    @endforelse
</section>
