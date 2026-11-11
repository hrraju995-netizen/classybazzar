<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class OrderInvoicePdfService
{
    /**
     * Generate a HMAC token that authorizes downloading a specific order's invoice PDF.
     */
    public function makeToken(Order $order): string
    {
        $secret = (string) config('app.key', 'classybazar-invoice-secret');

        return substr(hash_hmac('sha256', strtolower($order->order_number).'|'.$order->phone, $secret), 0, 32);
    }

    /**
     * Verify either a signed HMAC token or the customer's checkout phone number.
     */
    public function verifyAccess(Order $order, ?string $token = null, ?string $phone = null): bool
    {
        if (filled($token) && hash_equals($this->makeToken($order), trim((string) $token))) {
            return true;
        }

        if (filled($phone)) {
            $cleanInput = preg_replace('/\D/', '', (string) $phone);
            $cleanInput = preg_replace('/^88(?=01)/', '', $cleanInput);
            $cleanOrderPhone = preg_replace('/\D/', '', (string) $order->phone);
            $cleanOrderPhone = preg_replace('/^88(?=01)/', '', $cleanOrderPhone);

            if ($cleanInput !== '' && hash_equals($cleanOrderPhone, $cleanInput)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the public API URL to download the invoice PDF with a pre-signed token.
     */
    public function signedDownloadUrl(Order $order): string
    {
        return url('/api/orders/'.urlencode($order->order_number).'/invoice?token='.$this->makeToken($order));
    }

    /**
     * Generate the PDF and persist a copy under storage/app/public/invoices/.
     */
    public function generateAndStore(Order $order): string
    {
        $pdfBinary = $this->renderPdf($order);
        $path = 'invoices/Invoice-'.$order->order_number.'.pdf';

        Storage::disk('public')->put($path, $pdfBinary);

        return $path;
    }

    /**
     * Return an HTTP response that downloads (or previews inline) the order invoice PDF.
     */
    public function response(Order $order, bool $inline = false): Response
    {
        $pdfBinary = $this->renderPdf($order);
        $filename = 'Invoice-'.$order->order_number.'.pdf';
        $disposition = ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"';

        // Also keep stored copy fresh
        try {
            Storage::disk('public')->put('invoices/'.$filename, $pdfBinary);
        } catch (\Throwable) {
            // Ignore disk write failure and still stream the PDF to the user
        }

        return new Response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($pdfBinary),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Build a clean, professional A4 PDF invoice (%PDF-1.4) in pure PHP.
     */
    public function renderPdf(Order $order): string
    {
        $order->loadMissing(['items']);

        $siteName = $this->cleanText((string) Setting::get('site.name', 'Classybazar'));
        $siteTagline = $this->cleanText((string) Setting::get('site.tagline', 'Gadgets & Accessories Store'));
        $supportPhone = $this->cleanText((string) Setting::get('contact.phone', '+880 1XXX-XXXXXX'));
        $supportEmail = $this->cleanText((string) Setting::get('contact.email', 'support@classybazar.com'));
        $supportAddress = $this->cleanText((string) Setting::get('contact.address', 'Dhaka, Bangladesh'));

        // Page geometry: A4 (595.28 x 841.89 pt)
        $marginL = 40.0;
        $marginR = 555.0;
        $contentW = $marginR - $marginL; // 515 pt

        $ops = [];

        // 1. Top brand accent bar
        $ops[] = $this->rectFill(0, 832, 595.28, 10, [0.92, 0.43, 0.11]); // #EB6E1B orange bar

        // 2. Header section background card
        $ops[] = $this->rectFill($marginL, 730, $contentW, 82, [0.06, 0.09, 0.16]); // #0F172A slate-900

        // Store Name & Contact on left of header
        $ops[] = $this->text($marginL + 18, 786, strtoupper($siteName), 'F2', 20, [1.0, 1.0, 1.0]);
        $ops[] = $this->text($marginL + 18, 770, $siteTagline ?: 'Official Order Invoice & Receipt', 'F1', 9.5, [0.95, 0.55, 0.25]);

        $contactLine = trim(implode('  |  ', array_filter([$supportPhone, $supportEmail])));
        if ($contactLine !== '') {
            $ops[] = $this->text($marginL + 18, 754, $contactLine, 'F1', 8.5, [0.80, 0.84, 0.90]);
        }
        if ($supportAddress !== '') {
            $ops[] = $this->text($marginL + 18, 741, $this->truncate($supportAddress, 65), 'F1', 8.0, [0.65, 0.70, 0.78]);
        }

        // Right side of header: INVOICE title & Order #
        $ops[] = $this->textRight($marginR - 18, 786, 'ORDER INVOICE', 'F2', 16, [0.95, 0.55, 0.25]);
        $ops[] = $this->textRight($marginR - 18, 768, 'Order #: '.$this->cleanText($order->order_number), 'F2', 11, [1.0, 1.0, 1.0]);
        $placedDate = $order->created_at ? $order->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A');
        $ops[] = $this->textRight($marginR - 18, 753, 'Date: '.$placedDate, 'F1', 8.5, [0.80, 0.84, 0.90]);
        $ops[] = $this->textRight($marginR - 18, 740, 'Status: '.strtoupper($this->cleanText($order->statusLabel())), 'F2', 8.5, [0.30, 0.85, 0.50]);

        // 3. Two-column Customer & Order Info boxes (y: 612 to 716)
        $boxTop = 716.0;
        $boxH = 104.0;
        $boxY = $boxTop - $boxH;
        $colW = 250.0;
        $col2X = $marginL + $colW + 15.0;

        // Left Box: Customer / Delivery Details
        $ops[] = $this->rectFillStroke($marginL, $boxY, $colW, $boxH, [0.98, 0.98, 0.99], [0.88, 0.90, 0.93]);
        $ops[] = $this->rectFill($marginL, $boxTop - 22, $colW, 22, [0.95, 0.96, 0.98]);
        $ops[] = $this->text($marginL + 12, $boxTop - 15, 'BILLING & DELIVERY DETAILS', 'F2', 8.5, [0.92, 0.43, 0.11]);

        $ops[] = $this->text($marginL + 12, $boxTop - 34, $this->truncate($this->cleanText($order->customer_name), 38), 'F2', 11, [0.07, 0.09, 0.15]);
        $ops[] = $this->text($marginL + 12, $boxTop - 49, 'Phone: '.$this->cleanText($order->phone), 'F1', 9.5, [0.22, 0.25, 0.32]);

        $emailLine = filled($order->email) ? 'Email: '.$this->cleanText((string) $order->email) : 'Zone: '.($order->shipping_area === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka');
        $ops[] = $this->text($marginL + 12, $boxTop - 63, $this->truncate($emailLine, 42), 'F1', 9.0, [0.22, 0.25, 0.32]);

        $addressLines = $this->wrapText('Address: '.$this->cleanText($order->address), 42, 2);
        $addrY = $boxTop - 78;
        foreach ($addressLines as $line) {
            $ops[] = $this->text($marginL + 12, $addrY, $line, 'F1', 8.8, [0.30, 0.34, 0.42]);
            $addrY -= 12;
        }

        // Right Box: Order & Payment Summary
        $ops[] = $this->rectFillStroke($col2X, $boxY, $colW, $boxH, [0.98, 0.98, 0.99], [0.88, 0.90, 0.93]);
        $ops[] = $this->rectFill($col2X, $boxTop - 22, $colW, 22, [0.95, 0.96, 0.98]);
        $ops[] = $this->text($col2X + 12, $boxTop - 15, 'ORDER & PAYMENT INFORMATION', 'F2', 8.5, [0.92, 0.43, 0.11]);

        $paymentMethod = strtoupper($this->cleanText((string) ($order->payment_method ?: 'COD')));
        if ($paymentMethod === 'COD') {
            $paymentMethod = 'Cash on Delivery (COD)';
        }
        $paymentStatus = strtoupper($this->cleanText((string) ($order->payment_status ?: 'pending')));
        $shippingAreaLabel = $order->shipping_area === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka';
        $courierLabel = filled($order->carrier)
            ? $this->cleanText($order->carrier).(filled($order->tracking_code) ? ' ('.$this->cleanText($order->tracking_code).')' : '')
            : (filled($order->courier) ? strtoupper($this->cleanText($order->courier)) : 'Standard Delivery');

        $infoRows = [
            ['Payment Method:', $paymentMethod],
            ['Payment Status:', $paymentStatus],
            ['Delivery Area:', $shippingAreaLabel],
            ['Courier / Track:', $this->truncate($courierLabel, 26)],
            ['Est. Delivery:', $order->estimated_delivery_at ? $order->estimated_delivery_at->format('d M Y') : '2 - 4 Days'],
        ];

        $infoY = $boxTop - 36;
        foreach ($infoRows as [$label, $val]) {
            $ops[] = $this->text($col2X + 12, $infoY, $label, 'F1', 8.8, [0.40, 0.45, 0.53]);
            $ops[] = $this->textRight($col2X + $colW - 12, $infoY, $val, 'F2', 8.8, [0.07, 0.09, 0.15]);
            $infoY -= 14.5;
        }

        // 4. Order Items Table
        $tableTop = $boxY - 18.0;
        $headerH = 24.0;

        // Table Header Bar
        $ops[] = $this->rectFill($marginL, $tableTop - $headerH, $contentW, $headerH, [0.12, 0.16, 0.23]); // #1E293B
        $headerTextY = $tableTop - 15.5;
        $ops[] = $this->text($marginL + 10, $headerTextY, '#', 'F2', 9, [1, 1, 1]);
        $ops[] = $this->text($marginL + 32, $headerTextY, 'PRODUCT DESCRIPTION', 'F2', 9, [1, 1, 1]);
        $ops[] = $this->text($marginL + 285, $headerTextY, 'TYPE', 'F2', 9, [1, 1, 1]);
        $ops[] = $this->textRight($marginL + 365, $headerTextY, 'QTY', 'F2', 9, [1, 1, 1]);
        $ops[] = $this->textRight($marginL + 438, $headerTextY, 'UNIT PRICE', 'F2', 9, [1, 1, 1]);
        $ops[] = $this->textRight($marginR - 10, $headerTextY, 'LINE TOTAL', 'F2', 9, [1, 1, 1]);

        $cursorY = $tableTop - $headerH;
        $items = $order->items;

        foreach ($items as $idx => $item) {
            $title = $this->cleanText((string) $item->title);
            $variantInfo = '';
            if (filled($item->variant_name)) {
                $variantInfo = 'Variation: '.$this->cleanText((string) $item->variant_name);
                if (filled($item->variant_sku)) {
                    $variantInfo .= ' (SKU: '.$this->cleanText((string) $item->variant_sku).')';
                }
            }

            $titleLines = $this->wrapText($title, 44, 2);
            $rowH = count($titleLines) > 1 || $variantInfo !== '' ? 34.0 : 25.0;

            // Keep space for totals at bottom of page
            if ($cursorY - $rowH < 175.0) {
                break;
            }

            $rowBottom = $cursorY - $rowH;
            $bg = ($idx % 2 === 1) ? [0.97, 0.98, 0.99] : [1.0, 1.0, 1.0];
            $ops[] = $this->rectFillStroke($marginL, $rowBottom, $contentW, $rowH, $bg, [0.91, 0.93, 0.95]);

            $firstLineY = $cursorY - 14.0;
            $ops[] = $this->text($marginL + 10, $firstLineY, (string) ($idx + 1), 'F1', 9, [0.40, 0.45, 0.53]);

            // Product title (1 or 2 lines) + variant subtitle
            $ops[] = $this->text($marginL + 32, $firstLineY, $titleLines[0] ?? 'Product', 'F2', 9.2, [0.07, 0.09, 0.15]);
            if (isset($titleLines[1])) {
                $ops[] = $this->text($marginL + 32, $firstLineY - 11.0, $titleLines[1], 'F1', 8.5, [0.20, 0.24, 0.32]);
            } elseif ($variantInfo !== '') {
                $ops[] = $this->text($marginL + 32, $firstLineY - 11.5, $this->truncate($variantInfo, 48), 'F1', 8.0, [0.45, 0.50, 0.58]);
            }

            $saleType = ($item->pricing_type === 'wholesale') ? 'Wholesale' : 'Retail';
            $ops[] = $this->text($marginL + 285, $firstLineY, $saleType, 'F1', 8.5, [0.35, 0.40, 0.48]);
            $ops[] = $this->textRight($marginL + 365, $firstLineY, (string) $item->quantity, 'F2', 9.2, [0.07, 0.09, 0.15]);
            $ops[] = $this->textRight($marginL + 438, $firstLineY, $this->formatBdt((int) $item->price), 'F1', 9.0, [0.20, 0.24, 0.32]);
            $ops[] = $this->textRight($marginR - 10, $firstLineY, $this->formatBdt((int) $item->subtotal), 'F2', 9.2, [0.07, 0.09, 0.15]);

            $cursorY = $rowBottom;
        }

        // 5. Totals & Notes Section below table
        $summaryTop = $cursorY - 14.0;
        $totalsX = $marginR - 220.0;
        $totalsW = 220.0;

        // Customer Note on the left if any
        if (filled($order->note)) {
            $noteBoxH = 56.0;
            $ops[] = $this->rectFillStroke($marginL, $summaryTop - $noteBoxH, 275.0, $noteBoxH, [1.0, 0.98, 0.94], [0.96, 0.85, 0.68]);
            $ops[] = $this->text($marginL + 10, $summaryTop - 14, 'CUSTOMER NOTE:', 'F2', 8.2, [0.75, 0.32, 0.05]);
            $noteLines = $this->wrapText($this->cleanText((string) $order->note), 46, 2);
            $nY = $summaryTop - 27;
            foreach ($noteLines as $nLine) {
                $ops[] = $this->text($marginL + 10, $nY, $nLine, 'F1', 8.5, [0.25, 0.28, 0.35]);
                $nY -= 12;
            }
        }

        // Subtotal, Shipping, Discount rows
        $rowY = $summaryTop - 12.0;
        $ops[] = $this->text($totalsX + 10, $rowY, 'Subtotal:', 'F1', 9.5, [0.35, 0.40, 0.48]);
        $ops[] = $this->textRight($marginR - 10, $rowY, $this->formatBdt((int) $order->subtotal), 'F2', 9.5, [0.10, 0.14, 0.20]);

        $rowY -= 16.0;
        $ops[] = $this->text($totalsX + 10, $rowY, 'Shipping Charge:', 'F1', 9.5, [0.35, 0.40, 0.48]);
        $ops[] = $this->textRight($marginR - 10, $rowY, $this->formatBdt((int) $order->shipping_charge), 'F2', 9.5, [0.10, 0.14, 0.20]);

        if ((int) $order->discount > 0) {
            $rowY -= 16.0;
            $discLabel = 'Discount'.(filled($order->coupon_code) ? ' ('.$this->cleanText((string) $order->coupon_code).')' : '').':';
            $ops[] = $this->text($totalsX + 10, $rowY, $discLabel, 'F1', 9.5, [0.10, 0.60, 0.35]);
            $ops[] = $this->textRight($marginR - 10, $rowY, '- '.$this->formatBdt((int) $order->discount), 'F2', 9.5, [0.10, 0.60, 0.35]);
        }

        // Grand Total Box
        $totalBoxY = $rowY - 34.0;
        $ops[] = $this->rectFill($totalsX, $totalBoxY, $totalsW, 26.0, [0.92, 0.43, 0.11]); // #EB6E1B
        $ops[] = $this->text($totalsX + 12, $totalBoxY + 8.5, 'GRAND TOTAL:', 'F2', 10.5, [1.0, 1.0, 1.0]);
        $ops[] = $this->textRight($marginR - 10, $totalBoxY + 8.5, $this->formatBdt((int) $order->total), 'F2', 11.5, [1.0, 1.0, 1.0]);

        // 6. Footer
        $ops[] = $this->line($marginL, 62, $marginR, 62, [0.88, 0.90, 0.93], 0.8);
        $ops[] = $this->text($marginL, 46, 'Thank you for shopping with '.$siteName.'!', 'F2', 9.5, [0.15, 0.18, 0.25]);
        $ops[] = $this->text($marginL, 33, 'Keep this invoice for your records and warranty reference.', 'F1', 8.2, [0.45, 0.50, 0.58]);
        $ops[] = $this->textRight($marginR, 46, 'Generated: '.now()->format('d M Y, h:i A'), 'F1', 8.2, [0.45, 0.50, 0.58]);
        $ops[] = $this->textRight($marginR, 33, 'Order ID: '.$this->cleanText($order->order_number), 'F2', 8.2, [0.30, 0.35, 0.42]);

        $stream = implode("\n", $ops);

        return $this->assemblePdfDocument($stream);
    }

    protected function formatBdt(int $poisha): string
    {
        return 'BDT '.number_format(Money::toTaka($poisha), 2);
    }

    protected function cleanText(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $clean = $ascii !== false && $ascii !== '' ? $ascii : preg_replace('/[^\x20-\x7E]/', '', $text);

        return trim(preg_replace('/\s+/', ' ', (string) $clean));
    }

    protected function escapePdf(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    protected function truncate(string $text, int $maxChars): string
    {
        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return substr($text, 0, max(0, $maxChars - 3)).'...';
    }

    /**
     * @return array<int, string>
     */
    protected function wrapText(string $text, int $maxWidthChars, int $maxLines = 2): array
    {
        $wrapped = wordwrap($text, $maxWidthChars, "\n", true);
        $lines = array_values(array_filter(explode("\n", $wrapped), fn ($l) => $l !== ''));

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = $this->truncate($lines[$maxLines - 1], $maxWidthChars);
        }

        return $lines ?: [''];
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    protected function text(float $x, float $y, string $text, string $font, float $size, array $rgb): string
    {
        [$r, $g, $b] = $rgb;
        $escaped = $this->escapePdf($text);

        return sprintf('BT %.2f %.2f %.2f rg /%s %.2f Tf %.2f %.2f Td (%s) Tj ET', $r, $g, $b, $font, $size, $x, $y, $escaped);
    }

    /**
     * Approximate right-aligned text width for Helvetica/Helvetica-Bold.
     *
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    protected function textRight(float $rightX, float $y, string $text, string $font, float $size, array $rgb): string
    {
        $factor = $font === 'F2' ? 0.54 : 0.50;
        $approxWidth = strlen($text) * $size * $factor;
        $x = max(20.0, $rightX - $approxWidth);

        return $this->text($x, $y, $text, $font, $size, $rgb);
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    protected function rectFill(float $x, float $y, float $w, float $h, array $rgb): string
    {
        [$r, $g, $b] = $rgb;

        return sprintf('%.2f %.2f %.2f rg %.2f %.2f %.2f %.2f re f', $r, $g, $b, $x, $y, $w, $h);
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $fillRgb
     * @param  array{0: float, 1: float, 2: float}  $strokeRgb
     */
    protected function rectFillStroke(float $x, float $y, float $w, float $h, array $fillRgb, array $strokeRgb): string
    {
        [$fr, $fg, $fb] = $fillRgb;
        [$sr, $sg, $sb] = $strokeRgb;

        return sprintf('0.75 w %.2f %.2f %.2f rg %.2f %.2f %.2f RG %.2f %.2f %.2f %.2f re B', $fr, $fg, $fb, $sr, $sg, $sb, $x, $y, $w, $h);
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    protected function line(float $x1, float $y1, float $x2, float $y2, array $rgb, float $width = 1.0): string
    {
        [$r, $g, $b] = $rgb;

        return sprintf('%.2f w %.2f %.2f %.2f RG %.2f %.2f m %.2f %.2f l S', $width, $r, $g, $b, $x1, $y1, $x2, $y2);
    }

    protected function assemblePdfDocument(string $contentStream): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => "<< /Length ".strlen($contentStream)." >>\nstream\n".$contentStream."\nendstream",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}

