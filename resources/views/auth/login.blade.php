<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <title>login</title>
</head>

<body>
    <div class="flex min-h-screen justify-center items-center bg-[#000000]">
        <form class="flex flex-col space-y-4 " action="/login" method="POST">
            @csrf
            <input class="border-[#333333] bg-[#1A1A1A] text-[#FFFFFF] rounded p-2 w-full" name="email" type="email">
            @error('email')
            <p class="text-[#ffffff]"> {{$message}} </p>
            @enderror
            <input class="border-[#333333] bg-[#1A1A1A] text-[#FFFFFF] rounded p-2 w-full" name="password" type="password">
            <button class="bg-[#F5B122] text-black p-2 rounded w-full font-semibold" type="submit">Login</button>
        </form>
    </div>

</body>

</html>