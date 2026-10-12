<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | QA Kalibrasi</title>
    <link rel="icon" type="image/png" href="images/LogoTOTO.png?v=<?= time(); ?>"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <style>
        .posisitengah {
            margin: 0 auto;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">
    <div class="container mt-5">
        <br>
        <div class="col-md-6 posisitengah">
            
            <img src="images/LogoTOTO.png?v=<?= time(); ?>" width="360" class="rounded mx-auto d-block" style="margin-bottom: 50px;">
            
            <div class="card mt-4" style="border-radius: 10px;">
                <div class="card-body" padding: 30px;">
                    <form action="cek_login.php" method="POST" id="loginForm" autocomplete="on">
                        <div class="form-group">
                            <label for="exampleInputEmail1" style="font-weight: bold; color: #555;">User ID</label>
                            <input type="text" name="userid" class="form-control" placeholder="Enter User ID" required value="<?php echo (isset($_COOKIE['userid'])) ? $_COOKIE['userid']:'' ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1" style="font-weight: bold; color: #555;">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter Password" required value="<?php echo (isset($_COOKIE['password'])) ? $_COOKIE['password']:'' ?>">
                        </div>
                        <div class="form-group form-check" style="margin-bottom: 25px;">
                            <input type="checkbox" class="form-check-input" id="exampleCheck1" name="remember" <?php echo ((isset($_COOKIE['userid'])) and (isset($_COOKIE['password']))) ? "checked" : "" ?>>
                            <label class="form-check-label" for="exampleCheck1" style="color: #666;">Remember Me</label>
                        </div>
                        <div id="loginAlert" class="alert alert-danger" style="display:none; margin-top:15px; margin-bottom:0;" role="alert"></div>
                        <button type="submit" id="btnLogin" class="btn btn-primary btn-block" style="background-color: #005AAC; border: none; font-weight: bold; height: 45px;">Login</button>
                    </form>
                </div>
            </div>
            
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

    <script>
    (function () {
        var form = document.getElementById('loginForm');
        var alertBox = document.getElementById('loginAlert');
        var btn = document.getElementById('btnLogin');
        var userid = document.querySelector('input[name="userid"]');
        var password = document.querySelector('input[name="password"]');

        function showError(message) {
            alertBox.textContent = message || 'Login gagal.';
            alertBox.style.display = 'block';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alertBox.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = 'Memeriksa...';

            var formData = new FormData(form);
            formData.append('ajax', '1');

            fetch('cek_login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('Respons server tidak valid.');
                });
            })
            .then(function (result) {
                if (result.status === 'success') {
                    window.location.href = result.redirect || 'media.php';
                    return;
                }

                showError(result.message || 'ID atau password salah.');
                password.value = '';
                if (result.code === 'user_not_found' || result.code === 'blocked') {
                    userid.focus();
                } else {
                    password.focus();
                }
            })
            .catch(function (err) {
                showError(err.message || 'Gagal menghubungi server.');
                password.focus();
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = 'Login';
            });
        });
    })();
    </script>

</body>
</html>
