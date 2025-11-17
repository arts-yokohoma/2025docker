<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us Card</title>
    <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f2f2f2;
        font-family: Arial, sans-serif;
    }

    .card {
        background-color: #d99f5f; /* အရောင် */
        width: 300px;
        padding: 20px;
        border-radius: 15px;
        position: relative;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    }

    .card h2 {
        margin: 0 0 15px 0;
    }

    .screen {
        background-color: #7fc0ff;
        width: 200px;
        height: 120px;
        margin: 0 auto 15px auto;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 40px;
        color: #e0ffe0;
        transition: transform 0.3s;
    }

    .card:hover .screen {
        transform: scale(1.05);
    }

    .follow-btn {
        background-color: #0055ff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.3s;
    }

    .follow-btn:hover {
        background-color: #003bb5;
        transform: scale(1.05);
    }

    /* Ribbon corner */
    .ribbon {
        width: 50px;
        height: 50px;
        background-color: black;
        position: absolute;
        top: -5px;
        right: -5px;
        clip-path: polygon(0 0, 100% 0, 100% 60%, 60% 100%, 0 100%);
    }

    </style>
    </head>
    <body>

    <div class="card">
    <div class="ribbon"></div>
    <h2>About Us</h2>
    <div class="screen">UI</div>
    <button class="follow-btn">FOLLOW US</button>
    </div>

    </body>
    </html>