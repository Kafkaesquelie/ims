<?php
$page_title = 'Receipt of Returned SEmi-Expendable Property (RRSP)';
require_once('includes/load.php');
page_require_level(1); // Only admins

?>

<?php include_once('layouts/header.php'); ?>

  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 13px;
    }
    .container {
      width: 95%;
      margin: auto;
      border: 1px solid #000;
      padding: 10px;
    }
    h2 {
      text-align: center;
      font-size: 14px;
      margin: 5px 0;
      text-transform: uppercase;
    }
    .header-table, .items-table, .footer-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .header-table td {
      font-size: 12px;
      padding: 2px 5px;
    }
    .items-table th, .items-table td {
      border: 1px solid #000;
      text-align: center;
      padding: 4px;
      font-size: 12px;
    }
    .items-table th {
      background: #f2f2f2;
    }
    .footer-table td {
      text-align: center;
      padding: 20px 5px 5px 5px;
      font-size: 12px;
    }
    .signature {
      border-top: 1px solid #000;
      display: inline-block;
      padding-top: 2px;
      font-size: 12px;
    }
    .small-text {
      font-size: 11px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>RECEIPT OF RETURNED SEMI-EXPENDABLE PROPERTY (RRSP)</h2>
    
    <table class="header-table">
      <tr>
        <td>Entity Name: <b>Benguet State University - BOKOD CAMPUS</b></td>
        <td style="text-align:right;">Date: 09/08/2025</td>
      </tr>
      <tr>
        <td></td>
        <td style="text-align:right;">RRSP No. 2025-09-2001</td>
      </tr>
    </table>
    
    <p style="text-align:center; font-size:12px;">
      This is to acknowledge receipt of the returned Semi-expendable Property
    </p>
    
    <table class="items-table">
      <thead>
        <tr>
          <th>ITEM DESCRIPTION</th>
          <th>QTY</th>
          <th>ICS NO.</th>
          <th>END-USER</th>
          <th>REMARKS</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>MULTI-MEDIA PROJECTOR</td>
          <td>1</td>
          <td>xxxxxxx</td>
          <td>Heronima D. Sanchez</td>
          <td>Functional</td>
        </tr>
        <!-- Blank rows -->
        <?php for ($i=0; $i<12; $i++): ?>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
    
    <table class="footer-table">
      <tr>
        <td>
          <div class="signature">HERONIMA D. SANCHEZ</div><br>
          End User<br>
          <span class="small-text">September 8, 2025</span>
        </td>
        <td>
          <div class="signature">BRIGIDA A. BENSOSAN</div><br>
          Head, Property and/or Supply Division / Unit<br>
          <span class="small-text">September 8, 2025</span>
        </td>
      </tr>
      <tr>
        <td>Returned by:</td>
        <td>Received by:</td>
      </tr>
    </table>
  </div>
</body>
</html>

<?php include_once('layouts/footer.php'); ?>
