<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Forgot Password</title>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">

<div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
    <h2 class="text-xl font-semibold mb-4">Reset Password</h2>

    <form action="#" method="POST">
        @csrf
        <label class="block font-medium mb-1">Email Address</label>
        <input type="email" name="email" class="w-full border rounded-lg p-3 mb-4">

        <button class="w-full bg-black text-white py-3 rounded-lg hover:bg-gray-800">
            Send Reset Link
        </button>
    </form>
</div>

</body>
</html>
