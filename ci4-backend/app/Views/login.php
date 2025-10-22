<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Djati Intan Barokah</title>
    <style>
        /* [style CSS kamu sebelumnya, tidak diubah] */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
        }

        .container {
            display: flex;
            flex-direction: row;
            width: 750px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .left {
            flex: 1;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-right: 1px solid #e5e7eb;
        }

        .left img {
            max-width: 220px;
            height: auto;
        }

        .right {
            flex: 1;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h1 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 25px;
            color: #111827;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            font-size: 14px;
            display: block;
            margin-bottom: 6px;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #3b82f6;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #2563eb;
        }

        .error-msg {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        .success-msg {
            color: green;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 90%;
            }

            .left {
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                padding: 30px;
            }

            .right {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="left">
        <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo DIB">
    </div>

    <div class="right">
        <h1>Login</h1>

        <div id="alert" class="error-msg" style="display:none;"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Masuk</button>
        </form>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  const alertDiv = document.getElementById('alert');
  alertDiv.style.display = 'none';

  try {
    const response = await fetch('<?= site_url('api/login') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ username, password })
    });

    const data = await response.json().catch(() => ({}));

    if (response.ok) {
      alertDiv.className = 'success-msg';
      alertDiv.textContent = 'Login berhasil ! Selamat datang, ' + (data?.user?.username ?? '');
      alertDiv.style.display = 'block';

      setTimeout(() => {
        window.location.href = '<?= site_url('halaman-utama') ?>';
      }, 1500);
    } else {
      alertDiv.className = 'error-msg';
      alertDiv.textContent = data?.messages?.error || data?.message || 'Login gagal';
      alertDiv.style.display = 'block';
    }
  } catch (error) {
    console.error(error);
    alertDiv.className = 'error-msg';
    alertDiv.textContent = 'Terjadi kesalahan koneksi.';
    alertDiv.style.display = 'block';
  }
});
</script>

</body>
</html>
