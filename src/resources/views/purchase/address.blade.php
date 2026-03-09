@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase_address.css') }}">
@endsection

@section('content')

<div class="address-page">
    <div class="address-page__inner">
        <h1 class="address-title">住所の変更</h1>

        <form class="address-form"
            action="{{ route('purchase.address.update', ['item_id' => $item->id]) }}"
            method="POST">
            @csrf
            @method('PATCH')

            <div class="address-field">
                <label class="address-label" for="delivery_postcode">郵便番号</label>
                <input id="delivery_postcode" class="address-input" type="text"
                    name="delivery_postcode"
                    value="{{ old('delivery_postcode', $initial_postcode) }}"
                    placeholder="123-4567">
                @error('delivery_postcode')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="address-field">
                <label class="address-label" for="delivery_address">住所</label>
                <input id="delivery_address" class="address-input" type="text"
                    name="delivery_address"
                    value="{{ old('delivery_address', $initial_address) }}">
                @error('delivery_address')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="address-field">
                <label class="address-label" for="delivery_building">建物名</label>
                <input id="delivery_building" class="address-input" type="text"
                    name="delivery_building"
                    value="{{ old('delivery_building', $initial_building) }}">
                @error('delivery_building')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="address-submit">更新する</button>
        </form>
    </div>
</div>

@endsection