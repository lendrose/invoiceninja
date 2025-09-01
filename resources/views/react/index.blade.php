<!DOCTYPE html>
<html data-report-errors="{{ $report_errors }}" data-rc="{{ $rc }}" data-user-agent="{{ $user_agent }}" data-login="{{ $login }}">
<head>
  <meta charset="UTF-8">
  <title>{{ config('ninja.app_name') }}</title>
  <!-- <meta name="google-signin-client_id" content="{{ config('services.google.client_id') }}"> -->
  <meta name="description" content="Lendrose Pay is an invoicing and payment processing platform that allows you to accept payments from your customers." />

  @include('react.head')

</head>

<body class="h-full">
  <noscript>You need to enable JavaScript to run this app.</noscript>
  <div id="root"></div>
  
</body>
</html>
