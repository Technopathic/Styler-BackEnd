<html>
    <head>
        <title>Password Reset</title>

        <style>
          * {
            padding:0;
            margin:0;
          }
          .container {
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
          }

          .resetForm {
            width:400px;
            border:1px solid #BBBBBB;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            padding:20px;
            margin-top:30px;
            border-radius:5px;
            background:#EEEEEE;
          }

          .resetForm input {
            width:100%;
            padding:10px 5px;
            border-radius:5px;
            border:1px solid #CCCCCC;
          }

          .submit {
            background:#34c890;
            color:#EEEEEE;
            font-variant:small-caps;
            font-size:1.2em;
            margin-top:15px;
            cursor:pointer;
          }

          .submit:hover {
            background:#5cd3a6;
          }
        </style>
    </head>
    <body>
        <div class="container">
          <form action="{{ URL::to('confirmReset/'.app('request')->input('token')) }}" class="resetForm" method="POST">
            @if (Session::has('message'))
                <div>{{ Session::get('message') }}</div>
                <br/>
            @endif
            New Password:<br>
            <input type="password" name="password">
            <br>
            Confirm:<br>
            <input type="password" name="confirm">
            <br><br>
            <input type="submit" value="Submit" class="submit">
          </form>
        </div>
    </body>
</html>
