# PHP Local IP Project

🌐 **PHP Server Dashboard** - ローカルIPアドレス表示とシステム情報ダッシュボード

## 📋 概要

このプロジェクトは、PHPサーバーのローカルIPアドレスとシステム情報を表示するWebダッシュボードです。Apache + PHPとNginx + PHP-FPMの両方の環境をサポートし、包括的なPHP環境情報をエクスポートできます。

## ✨ 主な機能

### 🚀 軽量ヘルスチェック (index.php)
- サーバー稼働状況の即座確認
- 基本情報表示（IPアドレス、サーバー名、PHPバージョン）
- フルダッシュボードへのクイックアクセス

### 📊 詳細ダッシュボード (dashboard.php)
- **Full HD最適化レイアウト** - 1920x1080で全情報を一画面表示
- **リアルタイム情報表示**
  - ローカルIPアドレス
  - サーバー・コンテナ情報
  - PHP設定詳細（メモリ制限、実行時間制限等）
  - 拡張モジュール一覧
- **包括的CSVエクスポート** - phpinfo()の全情報をCSV形式でダウンロード

### 🐳 デュアルサーバー対応
- **Apache + PHP** (ポート8080)
- **Nginx + PHP-FPM** (ポート8081)

## 🛠️ 技術スタック

- **PHP 8.3** (最新安定版)
- **Apache 2.4** / **Nginx Alpine**
- **Tailwind CSS** (モダンUI)
- **Docker** & **Docker Compose**

## 📁 プロジェクト構成

```
php-local-ip-project/
├── index.php              # 軽量ヘルスチェックページ
├── dashboard.php           # 詳細ダッシュボード
├── Dockerfile             # Apache + PHP用
├── Dockerfile.nginx       # Nginx + PHP-FPM用
├── nginx.conf             # Nginx設定
├── docker-compose.yml     # マルチサーバー構成
├── .dockerignore          # Docker無視ファイル
└── README.md              # このファイル
```

## 🚀 クイックスタート

### 前提条件
- Docker
- Docker Compose

### 1. プロジェクトクローン
```bash
git clone <repository-url>
cd php-local-ip-project
```

### 2. サーバー起動
```bash
# 両サーバーを同時起動
docker compose up -d --build

# または個別にビルド
docker build -t php-apache .
docker build -t php-nginx -f Dockerfile.nginx .
```

### 3. アクセス
- **Apache サーバー**: http://localhost:8080
- **Nginx サーバー**: http://localhost:8081

## 📖 使用方法

### ヘルスチェック
各サーバーのルート (`/`) にアクセスすると軽量なヘルスチェックページが表示されます。

### フルダッシュボード
- ヘルスチェックページの「📊 Full Dashboard」ボタンをクリック
- または直接 `/dashboard.php` にアクセス

### CSVエクスポート
ダッシュボード上部の「PHPInfo CSV Download」ボタンで包括的なシステム情報をダウンロード可能。

## 🔧 設定詳細

### ポート設定
- Apache: `8080:80`
- Nginx: `8081:80`

### 動的コンテナ検出
各サーバーは自動的に以下を検出・表示します：
- コンテナ名 (php-apache-server / php-nginx-server)
- サーバータイプ (Apache + PHP / Nginx + PHP-FPM)
- ポートマッピング

### CSVエクスポート内容
- サーバー基本情報
- 完全なphpinfo()データ
- 全INI設定（Global/Local値）
- 拡張モジュール詳細（バージョン情報付き）
- システム環境情報

## 🎨 UI/UX特徴

- **Glass Morphism デザイン** - 半透明でモダンな外観
- **レスポンシブ対応** - モバイル・タブレット・デスクトップ対応
- **フェードインアニメーション** - 滑らかな表示効果
- **ホバー効果** - インタラクティブな要素
- **統一された高さ** - 全カラムが同じ高さで整列

## 🔄 開発・運用

### 開発用コマンド
```bash
# ログ確認
docker compose logs -f

# コンテナ状況確認
docker compose ps

# 停止
docker compose down

# 再ビルド
docker compose up -d --build
```

### ファイル更新
ホットリロード対応により、PHPファイルの変更は即座に反映されます。

## 📊 パフォーマンス

- **軽量ヘルスチェック**: 最小限のPHP処理でミリ秒レベルの応答
- **フルダッシュボード**: 包括的な情報表示でも高速レンダリング
- **CSVエクスポート**: 大量データも効率的に処理

## 🛡️ セキュリティ

- 入力値のHTMLエスケープ処理
- CSVインジェクション対策
- 適切なHTTPヘッダー設定

## 📈 今後の拡張予定

- [ ] メトリクス監視機能
- [ ] アラート通知機能
- [ ] 複数サーバー管理
- [ ] API エンドポイント
- [ ] ダークモード切り替え

## 🤝 コントリビューション

プルリクエストやイシューの報告を歓迎します。

## 📄 ライセンス

MIT License

## 📞 サポート

何かご質問がありましたら、Issueを作成してください。

---

**🐘 PHP 8.3 | 🐳 Docker | 🎨 Tailwind CSS**