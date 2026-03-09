<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\ExhibitionRequest;
use App\Models\Comment;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    /* 商品一覧（おすすめ / マイリスト / キーワード検索） */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $tab     = (string) $request->query('tab', 'recommend'); // recommend / mylist

        // 未ログインで mylist を開いたらログインへ
        if ($tab === 'mylist' && !Auth::check()) {
            return redirect()->route('login');
        }

        $query = Item::query()
            ->with(['images', 'purchase'])
            ->withCount(['likes', 'comments'])
            ->keywordSearch($keyword)
            ->orderByDesc('created_at');

        // マイリスト：いいねした商品だけ（＋検索もそのまま効く）
        if ($tab === 'mylist' && Auth::check()) {
            $query->whereHas('likes', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // おすすめ：ログイン中は自分の出品を除外（※おすすめの時だけ）
        if ($tab !== 'mylist' && Auth::check()) {
            $query->excludeMyItems();
        }

        $items = $query->get();

        return view('items.index', compact('items', 'tab', 'keyword'));
    }

    /* 商品詳細 */
    public function show(Item $item)
    {
        $item->load([
            'images',
            'user.profile',
            'categories',
            'purchase',
            'comments' => fn($q) => $q->latest(),
            'comments.user.profile',
        ])->loadCount([
            'likes',
            'comments',
        ]);

        $isLiked = Auth::check()
            ? $item->likes()->where('user_id', Auth::id())->exists()
            : false;

        return view('items.show', compact('item', 'isLiked'));
    }

    /* コメント投稿 */
    public function storeComment(CommentRequest $request, Item $item)
    {
        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
            'body'    => $request->comment,
        ]);

        return redirect()->route('items.show', ['item' => $item->id]);
    }

    /* いいね（ログイン必須） */
    public function like(Item $item)
    {
        if ($this->isOwnItem($item)) {
            return back();
        }

        Like::firstOrCreate([
            'user_id' => Auth::id(),
            'item_id' => $item->id,
        ]);

        return back();
    }

    /* いいね解除（ログイン必須） */
    public function unlike(Item $item)
    {
        if ($this->isOwnItem($item)) {
            return back();
        }

        Like::where('user_id', Auth::id())
            ->where('item_id', $item->id)
            ->delete();

        return back();
    }

    /* 出品画面 */
    public function create()
    {
        // バリデーションでリダイレクトしてきた直後以外は tmp_image を消す
        if (!session()->hasOldInput()) {
            session()->forget('tmp_image');
        }

        return view('items.create');
    }

    /* 出品処理 */
    public function store(ExhibitionRequest $request)
    {
        // 画像取り消しがチェックされていたら tmp を削除
        if ($request->boolean('tmp_image_remove')) {
            $tmp = $request->input('tmp_image') ?: session('tmp_image');

            if ($tmp && Storage::disk('public')->exists($tmp)) {
                Storage::disk('public')->delete($tmp);
            }

            session()->forget('tmp_image');
        }

        $item = Item::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'brand'       => $request->brand,
            'description' => $request->description,
            'price'       => $request->price,
            'condition'   => $request->condition,
            'sale_status' => 1, // 1 = 出品中
        ]);

        // カテゴリ（複数）
        $item->categories()->sync($request->category_ids);

        // 画像保存（1枚のみ、且つ画像データを保持）
        $path = null;

        if ($request->hasFile('image')) {
            // 直接本保存
            $path = $request->file('image')->store('items', 'public');
        } else {
            // tmp から本保存へ移動
            $tmp = $request->input('tmp_image') ?: session('tmp_image');

            if ($tmp && Storage::disk('public')->exists($tmp)) {
                $dest = 'items/' . basename($tmp);

                if (Storage::disk('public')->move($tmp, $dest)) {
                    $path = $dest;
                }
            }
        }

        // 画像が無い場合はエラーで戻す
        if (!$path) {
            return redirect()
                ->back()
                ->withErrors(['image' => '画像を選択してください'])
                ->withInput($request->except('image'));
        }

        ItemImage::create([
            'item_id'    => $item->id,
            'image_path' => $path,
        ]);

        session()->forget('tmp_image');

        return redirect()->route('mypage.show', ['page' => 'sell']);
    }

    /* 検索 */
    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $tab     = (string) $request->query('tab', 'recommend');

        return redirect()->route('items.index', array_filter([
            'tab'     => $tab,
            'keyword' => $keyword,
        ]));
    }

    /* Private: 自分が出品した商品かどうかを判定 */
    private function isOwnItem(Item $item): bool
    {
        return Auth::check() && (int) $item->user_id === (int) Auth::id();
    }
}
