<?php header( "refresh:5;url=http://localhost:3000" ); ?>

<html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
      <div class="container">
        @if (Session::has('message'))
            <div>{{ Session::get('message') }}</div>
            <br/>
            You will be redirected shortly.
        @endif
      </div>
    </body>
</html>
