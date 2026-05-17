<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <main>
        <h1>Login</h1>
        <form method="POST" action="/login">
            @csrf
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" required>
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div>
                <label for="remember">
                    <input id="remember" name="remember" type="checkbox">
                    Remember me
                </label>
            </div>
            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
