# 2025docker

This repository contains multiple team apps running behind Docker (Nginx + PHP/Python apps + MySQL/Postgres).

Below is a simple, beginner-friendly guide focused on **Team 5** (pizza ordering system).

## 1) Project overview (Team 5)

Team 5 is a pizza ordering system where customers choose a time slot, select pizza quantities, enter delivery details, and confirm an order.
Admins can manage shifts (which generate available time slots), view order history, and view/export customer contacts for promotions.
Time slot availability is capacity-based (driver count limits how many orders can be taken per slot).
When an order is confirmed, the customer contact (phone/name/address) is saved for future promotions.
The app runs in Docker with PHP + PostgreSQL 13, and uses Bootstrap for UI.

**Tech stack:** PHP (PDO), PostgreSQL 13, Docker Compose, Nginx reverse proxy.

## 2) Quick start (most important)

### Prerequisites

- Docker Desktop (Windows)

### Start

```powershell
docker compose up -d --build
```

### Open

- http://localhost/ (or your Nginx route if configured differently)

### Expected

- You should see the homepage.

## 3) Database setup (Team 5)

### Important note (Windows)

Run `psql` **inside the container**. The hostname `team_5_db` works only inside Docker networking and will not resolve from your Windows host.

### Create tables

**PowerShell-friendly (recommended on Windows):**

```powershell
Get-Content -Raw team_5/app/DB/create_orders.sql | docker exec -i team_5_postgres psql -U team_5 -d team_5_db
Get-Content -Raw team_5/app/DB/create_customer_contacts.sql | docker exec -i team_5_postgres psql -U team_5 -d team_5_db
```

**Bash example (Linux/macOS/WSL):**

```bash
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -f /dev/stdin < team_5/app/DB/create_orders.sql
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -f /dev/stdin < team_5/app/DB/create_customer_contacts.sql
```

### Verify

```powershell
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -c "\dt"
```

## 4) User ordering flow (simple)

1. Select a time slot
2. Select pizza quantities
3. Enter address + phone + name
4. Confirm order
5. Completion page shows reservation number

**Important rules**

- Order number format: `LLLNNN` (example: `ABC123`)
- Time slot capacity is enforced (driver count limits orders per slot)

## 5) Admin features

### How to log in

- Open the admin login page (from the UI footer or direct URL if you know it).

### Admin menu links

- Shift management (generates time slots)
- Orders history (supports searching by **order number** and **phone**)
- Customer contacts (view + CSV export for promotions)

## 6) Promotions / customer contacts

- When data is stored: **after customer confirms the order**
- Where stored: `customer_contacts` table in `team_5_db`
- Primary key: `phone` (stored as digits-only)
- Export: CSV from the admin customer contacts page

## 7) Troubleshooting (common)

### "psql can’t connect to team_5_db from my PC"

- Fix: use `docker exec ... psql ...` (run inside the container)

### "Table not found"

- Fix: run the SQL scripts inside the container (see Database setup section)

---

# 日本語版（Team 5）

このリポジトリは、Docker 上で複数チームのアプリ（Nginx + PHP/Python + MySQL/Postgres）を動かす構成になっています。

ここでは **Team 5（ピザ予約システム）** の使い方を、なるべく分かりやすくまとめます。

## 1) プロジェクト概要（Team 5）

Team 5 は、配達の時間帯（タイムスロット）を選んで、ピザの枚数を選択し、住所・電話番号・氏名を入力して予約（注文）するシステムです。
管理者はシフトを登録してタイムスロットを生成し、注文履歴の確認や、プロモーション用の顧客連絡先データの閲覧/CSV出力ができます。
タイムスロットは **ドライバー人数（配達員数）を上限** として、同じ枠で受けられる注文数が制限されます。
注文確定後に、顧客の電話番号・氏名・住所などがプロモーション用テーブルに保存されます。

**技術スタック:** PHP（PDO）, PostgreSQL 13, Docker Compose, Nginx（リバースプロキシ）

## 2) クイックスタート（最重要）

### 前提

- Docker Desktop（Windows）

### 起動

```powershell
docker compose up -d --build
```

### アクセス

- http://localhost/（Nginx のルーティング設定によっては別URLになる場合があります）

### 期待する画面

- ホームページが表示されます。

## 3) データベースセットアップ（Team 5）

### 重要（Windows）

`psql` は **コンテナの中で実行** してください。
`team_5_db` というホスト名は Docker ネットワーク内だけで有効なので、Windows ホスト側からは名前解決できません。

### テーブル作成（※基本はリポジトリルートで実行）

**PowerShell（Windows推奨）:**

```powershell
Get-Content -Raw team_5/app/DB/create_orders.sql | docker exec -i team_5_postgres psql -U team_5 -d team_5_db
Get-Content -Raw team_5/app/DB/create_customer_contacts.sql | docker exec -i team_5_postgres psql -U team_5 -d team_5_db
```

**Bash（Linux/macOS/WSL）例:**

```bash
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -f /dev/stdin < team_5/app/DB/create_orders.sql
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -f /dev/stdin < team_5/app/DB/create_customer_contacts.sql
```

### 確認

```powershell
docker exec -i team_5_postgres psql -U team_5 -d team_5_db -c "\dt"
```

## 4) お客様の注文フロー（簡単）

1. 時間帯（タイムスロット）を選ぶ
2. ピザの枚数を選ぶ
3. 住所 + 電話番号 + 氏名を入力
4. 内容を確認して確定
5. 完了画面に予約番号（注文番号）が表示されます

**重要ルール**

- 予約番号（注文番号）の形式: `LLLNNN`（例: `ABC123`）
- タイムスロットの上限はドライバー人数により制限されます

## 5) 管理者機能

### ログイン

- 管理者ログインページを開いてログインします（UIのフッターから移動、またはURL直打ち）。

### 管理メニュー

- シフト管理（タイムスロット生成）
- 注文管理（注文番号・電話番号で検索可能）
- 顧客連絡先（プロモーション用）（表示・CSV出力）

## 6) プロモーション用の顧客連絡先

- 保存タイミング: **注文の確定後**
- 保存先: `team_5_db` の `customer_contacts` テーブル
- 主キー: `phone`（数字のみで保存）
- CSV出力: 管理者の顧客連絡先ページから出力

## 7) よくあるトラブル

### 「PCから team_5_db に psql で接続できない」

- 対応: `docker exec ... psql ...` を使ってコンテナ内で実行

### 「テーブルが見つからない」

- 対応: コンテナ内で SQL を流し直す（データベースセットアップ参照）

---

Reference document (optional):
[docker_deployment\_\_\_database_guide.docx](https://github.com/user-attachments/files/24852553/docker_deployment___database_guide.docx)
