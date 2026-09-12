# shitate demo scale

**デモサイト専用**の WordPress プラグイン。[shitate](https://github.com/weblley/shitate) テーマのカスタマイザー「Typography Scale」（比率・基準サイズ・フォントサイズの丸め）を、**ログインしていない訪問者でも**モーダルから試せるようにします。

- 右下の「Aa」ボタン → モーダルで比率 / 基準サイズ / 丸めを変更すると、ページの `--st-*` トークンを即時に上書き
- 変更は訪問者のブラウザ（localStorage）にだけ記憶。**サーバー側には何も保存しない**
- 「テーマの設定に戻す」でサイトのカスタマイザー値に復帰
- shitate テーマ（または子テーマ）が有効なフロントエンドでのみ表示。`add_filter( 'sds_enabled', '__return_false' )` で無効化可
- **設定 → shitate demo scale**: 「ログイン時のみボタンを表示する」（既定オフ＝全訪問者に表示）

## インストール / 更新

1. [Releases](https://github.com/weblley/shitate-demo-scale/releases) の `shitate-demo-scale.zip` を「プラグイン → 新規追加 → アップロード」で導入
2. 以後は通常のプラグイン更新通知に新しい Release が出る（`Update URI` 経由で GitHub の最新 Release を参照）

## リリース手順（開発者向け）

```bash
# CHANGELOG.md に「## [X.Y.Z] - YYYY-MM-DD」を書いてから
bin/release.sh X.Y.Z
```

バージョン一括更新 → commit → tag → zip ビルド → push → GitHub Release 作成（zip 添付）まで自動。

## 仕組み

`assets/demo-scale.js` がテーマの `shitate_scale_inline_css()` と同じ CSS を生成し、`<head>` 末尾の `<style id="sds-override">` として注入します。テーマ側の計算式（`functions.php` / `assets/css/tokens.css`）を変えたら JS 内の `ROUND_CSS` / `RAW_CSS` も追従させてください。

命名: `sds` = shitate demo scale（定数 `SDS_*`、関数 `sds_*`、CSS `.sds-*`）。

## License

GPL-2.0-or-later
