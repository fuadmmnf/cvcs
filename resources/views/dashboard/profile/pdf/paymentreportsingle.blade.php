<html>
<head>
  <title>Report | Download | PDF</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
  body {
    font-family: 'kalpurush', sans-serif;
  }

  table {
      border-collapse: collapse;
      width: 100%;
  }
  table, td, th {
      border: 1px solid black;
  }
  th, td{
    padding: 7px;
    font-family: 'kalpurush', sans-serif;
    font-size: 15px;
  }
  @page {
    header: page-header;
    footer: page-footer;
    background-image: url({{ public_path('images/cvcs_background.png') }});
    background-size: cover;              
    background-repeat: no-repeat;
    background-position: center center;
  }
  </style>
</head>
<body>
  <h2 align="center">
    <img src="{{ public_path('images/custom2.png') }}" style="height: 100px; width: auto;"><br/>
    কাস্টমস অ্যান্ড ভ্যাট কো-অপারেটিভ সোসাইটি
  </h2>
  <p align="center" style="padding-top: -20px;">
    <span style="font-size: 18px;">পরিশোধের রিপোর্ট</span><br/>
    <span style="font-size: 25px;">
      <u>
        @if($payment->payment_status == 0)
          <span style="color: #5CB85C;">প্রক্রিয়াধীন</span>
        @else
          <span style="color: #D9534F;">অনুমোদিত</span>
        @endif
      </u>
    </span>
  </p>
  
  <div class="" style="padding-top: 20px;">
    <table class="">
      <tr style="">
        <td width="50%">
          <b>পরিশোধকারীঃ</b> {{ $payment->user->name_bangla }}
        </td>
        <td>
          সদস্যপদ আইডিঃ {{ $payment->member_id }}
        </td>
      </tr>
      <tr style="">
        <td colspan="2"><b>জমাদানকারী</b> {{ $payment->payee->name_bangla }}</td>
      </tr>
    </table>
  </div>

  <div class="" style="padding-top: 50px;">
    <table class="" style="background: rgba(219, 243, 252, 0.4);">
      <tr>
        <th width="50%">ধরণ</th>
        <td>
          @if($payment->payment_category == 0)
            সদস্যপদ বাবদ
          @else
            মাসিক পরিশোধ
          @endif
        </td>
      </tr>
      <tr>
        <th>পে স্লিপ</th>
        <td>{{ $payment->pay_slip }}</td>
      </tr>
      <tr>
        <th>পেমেন্ট আইডি</th>
        <td>{{ $payment->payment_key }}</td>
      </tr>
      <tr>
        <th>পেমেন্ট টাইপ</th>
        <td>
          @if($payment->payment_type == 1)
            <b>SINGLE</b>
          @elseif($payment->payment_type == 2)
            <b>BULK</b>
          @endif
        </td>
      </tr>
      <tr>
        <th>পরিমাণ</th>
        <td><big>৳ {{ $payment->amount }}</big></td>
      </tr>
      <tr>
        <th>ব্যাংক ও ব্রাঞ্চ</th>
        <td>{{ $payment->bank }}, {{ $payment->branch }}</td>
      </tr>
      <tr>
        <th>সময়কাল</th>
        <td>{{ date('F d, Y, h:m:i A', strtotime($payment->created_at)) }}</td>
      </tr>
    </table>
  </div>

  <htmlpagefooter name="page-footer">
    <small>ডাউনলোডের সময়কালঃ <span style="font-family: Calibri;">{{ date('F d, Y, h:i A') }}</span></small><br/>
    <small style="font-family: Calibri; color: #6D6E6A;">Generated by: https://cvcsbd.com</small>
  </htmlpagefooter>
</body>
</html>