<?php

namespace AaoSikheSystem\helper;

use AaoSikheSystem\Security\Crypto;
use DateTime;
use DateTimeZone;

/**
 * Validation View Helper - Template-level validation display with full features
 * 
 * @package AaoSikheSystem
 */
class Utility
{
    public static function timeAgo(int|string $time, $supix = 'ago'): string
    {
        // Convert date string to timestamp
        if (!is_int($time)) {
            $time = strtotime($time);
        }

        if (!$time || $time > time()) {
            return 'Just now';
        }

        $diff = time() - $time;

        $units = [
            'year'   => 31536000,
            'month'  => 2592000,
            'week'   => 604800,
            'day'    => 86400,
            'hour'   => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        foreach ($units as $name => $seconds) {
            if ($diff >= $seconds) {
                $value = floor($diff / $seconds);
                return $value . ' ' . $name . ($value > 1 ? 's' : '') . $supix;
            }
        }

        return 'Just now';
    }
    public static function spaceToUnderscore(string $value, $unserscore = '_'): string
    {
        return str_replace(' ', $unserscore, trim($value));
    }

    static function  getFirstLastInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $count = count($parts);

        if ($count === 1) {
            return strtoupper($parts[0][0]);
        }

        return strtoupper($parts[0][0] . $parts[$count - 1][0]);
    }
    static  function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';

        foreach ($parts as $part) {
            $initials .= strtoupper($part[0]);
        }

        return $initials;
    }

    public static function parseCode(string $code): ?array
    {
        // Match: Prefix (letters) + 8 digit date + serial number
        if (preg_match('/^([A-Za-z]+)(\d{8})(\d+)$/', $code, $matches)) {

            $prefix     = $matches[1];
            $dateRaw    = $matches[2];
            $serial     = $matches[3];

            $date = DateTime::createFromFormat('Ymd', $dateRaw);

            if (!$date) {
                return null;
            }

            return [
                'prefix'        => $prefix,
                'date_raw'      => $dateRaw,
                'date_formatted' => $date->format('Y-m-d'),
                'timestamp'     => $date->getTimestamp(),
                'serial'        => $serial
            ];
        }

        return null;
    }
    public static function stringToColor(string $string): string
    {
        $string = trim(strtolower($string));

        if ($string === '') {
            return '#6b7280'; // fallback gray
        }

        // Create hash
        $hash = md5($string);

        // Convert hash to RGB
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        /* ===============================
         * Normalize for pleasant colors
         * =============================== */
        $r = ($r + 128) / 2;
        $g = ($g + 128) / 2;
        $b = ($b + 128) / 2;

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public static function profileImageData(?string $imageName): array
    {
        if (empty($imageName)) {
            return [
                'type'  => 'initials',
                'url'   => null
            ];
        }

        $path = PathManager::absolute('profile_pictures') . '/' . $imageName;

        if (is_file($path) && file_exists($path)) {
            $key   = hash('sha256', APP_KEY, true);
            $token =  Crypto::encryptImagePath(
                PathManager::relative('profile_pictures') . '/' . $imageName,
                $key
            );

            return [
                'type' => 'image',
                'url'  => UrlHelper::route('media.view', [], ['media' => $token])
            ];
        }

        return [
            'type' => 'initials',
            'url'  => null
        ];
    }
    public static function formatDateWithAge(int $timestamp, string $timezone = 'Asia/Kolkata')
    {
        if (empty($timestamp) || !is_numeric($timestamp)) {
            return 'Invalid date';
        }

        // Create DateTime from timestamp
        $dob = new DateTime("@$timestamp");
        $dob->setTimezone(new DateTimeZone($timezone));

        // Format readable date
        $formattedDate = $dob->format('F d, Y');

        // Calculate age
        $today = new DateTime('now', new DateTimeZone($timezone));
        $age = $today->diff($dob)->y;

        return $formattedDate . " (" . $age . " years)";
    }

    public static function profileRender(
        array $student,
        string $class = '',
        string $style = '',
        array $options = [],
        string $id = ''
    ): string {
        // Default options
        $size       = $options['size'] ?? 32;
        $fontSize   = $options['font_size'] ?? 12;
        $defaultColor = $options['default_color'] ?? '#6b7280';

        $avatarType  = $student['avatar_type'] ?? null;
        $avatarUrl   = $student['avatar_url'] ?? null;
        $avatarColor = $student['avatar_color'] ?? $defaultColor;
        $initials    = $student['initials'] ?? 'AS';

        // Escape values
        $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        $style = htmlspecialchars($style, ENT_QUOTES, 'UTF-8');
        $color = htmlspecialchars($avatarColor, ENT_QUOTES, 'UTF-8');
        $initials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

        // ================= IMAGE AVATAR =================
        if ($avatarType === 'image' && !empty($avatarUrl)) {

            $url = htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8');
            $class = (empty($class)) ? 'student-avatar-img ' : $class;
            return '
            <img
                src="' . $url . '"
                alt="Student Avatar"
                class=" ' . $class . '"
                id="' . $id . '"
                style="
                    width:' . $size . 'px;
                    height:' . $size . 'px;
                    border-radius:50%;
                    object-fit:cover;
                    ' . $style . '
                "
            >
        ';
        }
        $class = (empty($class)) ? 'user-avatar ' : $class;
        // ================= INITIALS AVATAR =================
        return '
        <div class=" ' . $class . '"
        id="' . $id . '"
             style="
                width:' . $size . 'px;
                height:' . $size . 'px;
                font-size:' . $fontSize . 'px;
                background:' . $color . ';
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                color:#fff;
                font-weight:600;
                ' . $style . '
             ">
            ' . $initials . '
        </div>
    ';
    }


    /**
     * Get random linear gradient background
     *
     * @return string
     */
    public static function  getRandomGradient(): string
    {
        static $gradients = [

            "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
            "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)",
            "linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)",
            "linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)",
            "linear-gradient(135deg, #fa709a 0%, #fee140 100%)",
            "linear-gradient(135deg, #30cfd0 0%, #330867 100%)",
            "linear-gradient(135deg, #5f72bd 0%, #9b23ea 100%)",
            "linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)",
            "linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)",
            "linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)",

            "linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)",
            "linear-gradient(135deg, #ff8177 0%, #ff867a 100%)",
            "linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)",
            "linear-gradient(135deg, #56ab2f 0%, #a8e063 100%)",
            "linear-gradient(135deg, #614385 0%, #516395 100%)",
            "linear-gradient(135deg, #e96443 0%, #904e95 100%)",
            "linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%)",
            "linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)",

            "linear-gradient(135deg, #cc2b5e 0%, #753a88 100%)",
            "linear-gradient(135deg, #ee9ca7 0%, #ffdde1 100%)",
            "linear-gradient(135deg, #42275a 0%, #734b6d 100%)",
            "linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%)",
            "linear-gradient(135deg, #de6161 0%, #2657eb 100%)",
            "linear-gradient(135deg, #ff9966 0%, #ff5e62 100%)",
            "linear-gradient(135deg, #00b09b 0%, #96c93d 100%)",
            "linear-gradient(135deg, #fc466b 0%, #3f5efb 100%)",
            "linear-gradient(135deg, #7f00ff 0%, #e100ff 100%)",
            "linear-gradient(135deg, #11998e 0%, #38ef7d 100%)",
            "linear-gradient(135deg, #ff7eb3 0%, #ff758c 100%)",
            "linear-gradient(135deg, #6dd5fa 0%, #2980b9 100%)",
            "linear-gradient(135deg, #ff512f 0%, #dd2476 100%)",
            "linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)",
            "linear-gradient(135deg, #ffafbd 0%, #ffc3a0 100%)",
            "linear-gradient(135deg, #2196f3 0%, #21cbf3 100%)",
            "linear-gradient(135deg, #cc95c0 0%, #dbd4b4 100%)",
            "linear-gradient(135deg, #ee0979 0%, #ff6a00 100%)",
            "linear-gradient(135deg, #42275a 0%, #734b6d 100%)",
            "linear-gradient(135deg, #141e30 0%, #243b55 100%)",

            "linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%)",
            "linear-gradient(135deg, #8360c3 0%, #2ebf91 100%)",
            "linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)",
            "linear-gradient(135deg, #30e8bf 0%, #ff8235 100%)",
            "linear-gradient(135deg, #654ea3 0%, #eaafc8 100%)",
            "linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)",
            "linear-gradient(135deg, #f953c6 0%, #b91d73 100%)",
            "linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%)",
            "linear-gradient(135deg, #43cea2 0%, #185a9d 100%)",
            "linear-gradient(135deg, #ffb347 0%, #ffcc33 100%)",

            "linear-gradient(135deg, #2c3e50 0%, #fd746c 100%)",
            "linear-gradient(135deg, #de6262 0%, #ffb88c 100%)",
            "linear-gradient(135deg, #06beb6 0%, #48b1bf 100%)",
            "linear-gradient(135deg, #eb3349 0%, #f45c43 100%)",
            "linear-gradient(135deg, #dd5e89 0%, #f7bb97 100%)",
            "linear-gradient(135deg, #56ccf2 0%, #2f80ed 100%)",
            "linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)",
            "linear-gradient(135deg, #373b44 0%, #4286f4 100%)",
            "linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%)",
            "linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%)",

            "linear-gradient(135deg, #ffb75e 0%, #ed8f03 100%)",
            "linear-gradient(135deg, #000428 0%, #004e92 100%)",
            "linear-gradient(135deg, #3a1c71 0%, #d76d77 100%)",
            "linear-gradient(135deg, #ff4e50 0%, #f9d423 100%)",
            "linear-gradient(135deg, #24c6dc 0%, #514a9d 100%)",
            "linear-gradient(135deg, #eecda3 0%, #ef629f 100%)",
            "linear-gradient(135deg, #ff9966 0%, #ff5e62 100%)",
            "linear-gradient(135deg, #7f7fd5 0%, #86a8e7 100%)",
            "linear-gradient(135deg, #c94b4b 0%, #4b134f 100%)",
            "linear-gradient(135deg, #00f260 0%, #0575e6 100%)",

            "linear-gradient(135deg, #e65c00 0%, #f9d423 100%)",
            "linear-gradient(135deg, #1f4037 0%, #99f2c8 100%)",
            "linear-gradient(135deg, #ff512f 0%, #f09819 100%)",
            "linear-gradient(135deg, #b24592 0%, #f15f79 100%)",
            "linear-gradient(135deg, #134e5e 0%, #71b280 100%)",
            "linear-gradient(135deg, #ff0844 0%, #ffb199 100%)",
            "linear-gradient(135deg, #4ca1af 0%, #c4e0e5 100%)",
            "linear-gradient(135deg, #ffe000 0%, #799f0c 100%)",
            "linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%)",
            "linear-gradient(135deg, #fc5c7d 0%, #6a82fb 100%)",

            "linear-gradient(135deg, #355c7d 0%, #6c5b7b 100%)",
            "linear-gradient(135deg, #ff00cc 0%, #333399 100%)",
            "linear-gradient(135deg, #0cebeb 0%, #20e3b2 100%)",
            "linear-gradient(135deg, #f12711 0%, #f5af19 100%)",
            "linear-gradient(135deg, #3c1053 0%, #ad5389 100%)",
            "linear-gradient(135deg, #667db6 0%, #0082c8 100%)",
            "linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%)",
            "linear-gradient(135deg, #0f3443 0%, #34e89e 100%)",
            "linear-gradient(135deg, #ffb347 0%, #ffcc33 100%)",
            "linear-gradient(135deg, #2b5876 0%, #4e4376 100%)",

            "linear-gradient(135deg, #ff6a00 0%, #ee0979 100%)",
            "linear-gradient(135deg, #8360c3 0%, #2ebf91 100%)",
            "linear-gradient(135deg, #11998e 0%, #38ef7d 100%)",
            "linear-gradient(135deg, #f953c6 0%, #b91d73 100%)",
            "linear-gradient(135deg, #ff512f 0%, #dd2476 100%)",
            "linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%)",
            "linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%)",
            "linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)",
            "linear-gradient(135deg, #667db6 0%, #0082c8 100%)",
            "linear-gradient(135deg, #f7971e 0%, #ffd200 100%)",

            "linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)",
            "linear-gradient(135deg, #30cfd0 0%, #330867 100%)",
            "linear-gradient(135deg, #5f72bd 0%, #9b23ea 100%)",
            "linear-gradient(135deg, #ffafbd 0%, #ffc3a0 100%)",
            "linear-gradient(135deg, #654ea3 0%, #eaafc8 100%)",
            "linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)",
            "linear-gradient(135deg, #cc2b5e 0%, #753a88 100%)",
            "linear-gradient(135deg, #de6161 0%, #2657eb 100%)",
            "linear-gradient(135deg, #7f00ff 0%, #e100ff 100%)",
            "linear-gradient(135deg, #ff9966 0%, #ff5e62 100%)"


            // Duplicate more variations to reach 100+
        ];

        return $gradients[array_rand($gradients)];
    }


    public static function getRandomGradient_auto(): string
    {
        static $gradients = null;

        if ($gradients === null) {

            $gradients = [];

            // Generate 1000 gradients
            for ($i = 0; $i < 1000; $i++) {

                // Random colors
                $color1 = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $color2 = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $color3 = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                // Random angle
                $angle = mt_rand(0, 360);

                // Random type
                $type = mt_rand(1, 3);

                switch ($type) {

                    // 2-color linear
                    case 1:
                        $gradients[] = "linear-gradient({$angle}deg, {$color1} 0%, {$color2} 100%)";
                        break;

                    // 3-color linear
                    case 2:
                        $gradients[] = "linear-gradient({$angle}deg, {$color1} 0%, {$color2} 50%, {$color3} 100%)";
                        break;

                    // radial
                    case 3:
                        $gradients[] = "radial-gradient(circle, {$color1} 0%, {$color2} 50%, {$color3} 100%)";
                        break;
                }
            }
        }

        return $gradients[array_rand($gradients)];
    }

    public static function getRandomGradient_type(string $type = 'all', string $angles = null): string
    {
        static $gradients = null;

        if ($gradients === null) {

            $gradients = [
                'linear' => [],
                'radial' => []
            ];

            $angles = $angles ?? range(0, 360, 15);

            // Generate 120 random base colors
            $baseColors = [];
            for ($i = 0; $i < 120; $i++) {
                $baseColors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            }

            foreach ($angles as $angle) {

                foreach ($baseColors as $c1) {

                    foreach ($baseColors as $c2) {

                        if ($c1 === $c2) continue;

                        // 2-color linear
                        $gradients['linear'][] =
                            "linear-gradient({$angle}deg, {$c1} 0%, {$c2} 100%)";

                        // 3-color linear
                        $c3 = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        $gradients['linear'][] =
                            "linear-gradient({$angle}deg, {$c1}, {$c2}, {$c3})";

                        // 2-color radial
                        $gradients['radial'][] =
                            "radial-gradient(circle at center, {$c1} 0%, {$c2} 100%)";

                        // 3-color radial
                        $gradients['radial'][] =
                            "radial-gradient(ellipse at center, {$c1}, {$c2}, {$c3})";

                        // Stop at ~6000 total
                        if (
                            count($gradients['linear']) +
                            count($gradients['radial']) >= 6000
                        ) {
                            break 3;
                        }
                    }
                }
            }
        }

        // Return based on requested type
        if ($type === 'linear') {
            return $gradients['linear'][array_rand($gradients['linear'])];
        }

        if ($type === 'radial') {
            return $gradients['radial'][array_rand($gradients['radial'])];
        }

        // Default: random from both
        $all = array_merge($gradients['linear'], $gradients['radial']);
        return $all[array_rand($all)];
    }
}
