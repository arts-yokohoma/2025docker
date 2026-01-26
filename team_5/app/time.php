<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="img/nav_bar_logo.png" height="60" class="me-2" alt="Team 5 logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <button class="btn btn-contact rounded-pill px-4">お問い合わせ</button>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->


    <form id="timeSlotForm" action="order_select.php" method="get">
        <input type="hidden" name="time_slot" id="selectedTimeSlot" value="">

        <div class="container mt-3">
            <div id="time-slots" class="row g-4 text-center"></div>
        </div>

        <div class="d-flex justify-content-center my-5">
            <button id="submitBtn" type="submit" class="btn btn-success fs-3 px-5" disabled>
                メニューへ進む
            </button>
        </div>
    </form>



    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        const startTime = "10:00";
        const endTime = "22:00";
        const intervalMinutes = 30;

        // Full (満席) slots
        const fullSlots = [
            "12:30-13:00",
            "17:30-18:00"
        ];

        const container = document.getElementById("time-slots");
        const selectedTimeSlot = document.getElementById("selectedTimeSlot");
        const submitBtn = document.getElementById("submitBtn");
        const form = document.getElementById("timeSlotForm");

        function timeToMinutes(time) {
            const [h, m] = time.split(":").map(Number);
            return h * 60 + m;
        }

        function minutesToTime(minutes) {
            const h = String(Math.floor(minutes / 60)).padStart(2, "0");
            const m = String(minutes % 60).padStart(2, "0");
            return `${h}:${m}`;
        }

        let current = timeToMinutes(startTime);
        const end = timeToMinutes(endTime);

        while (current < end) {
            const next = current + intervalMinutes;

            const label = `${minutesToTime(current)}-${minutesToTime(next)}`;
            const isFull = fullSlots.includes(label);

            const col = document.createElement("div");
            col.className = "col-6 col-md-3";

            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "btn w-100 py-4 time-slot-btn";
            btn.disabled = isFull;
            btn.dataset.slot = label;
            btn.innerHTML = isFull ?
                `${label}<br><span class="text-danger">満席</span>` :
                label;

            if (!isFull) {
                btn.addEventListener('click', function() {
                    // only one selected at a time
                    container.querySelectorAll('.time-slot-btn.is-selected').forEach(b => {
                        b.classList.remove('is-selected');
                        b.setAttribute('aria-pressed', 'false');
                    });

                    btn.classList.add('is-selected');
                    btn.setAttribute('aria-pressed', 'true');
                    selectedTimeSlot.value = btn.dataset.slot;
                    submitBtn.disabled = false;
                });
                btn.setAttribute('aria-pressed', 'false');
            }

            col.appendChild(btn);
            container.appendChild(col);

            current = next;
        }

        form.addEventListener('submit', function(e) {
            if (!selectedTimeSlot.value) {
                e.preventDefault();
                alert('時間を1つ選択してください。');
            }
        });
    </script>


    <!-- Site footer -->
    <footer class="site-footer mt-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <a class="navbar-brand d-flex align-items-center" href="index.php">
                        <img src="img/nav_bar_logo.png" height="40" class="me-2" alt="Team 5 logo">
                    </a>
                    <small class="d-block">&copy; <span id="year"></span> CYBER EDGE. All rights reserved.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 footer-links">
                        <li class="list-inline-item"><a href="/index.php">ホーム</a></li>
                        <li class="list-inline-item"><a href="admin_login.php">Login</a></li>
                        <li class="list-inline-item"><a href="#">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>

</html>