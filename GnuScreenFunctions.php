<?php

declare(strict_types=1);

class GnuScreenFunctions
{
    public static function getScreenList(): array
    {
        /*
        sample input: 
There are screens on:
	330901.screen with spaces	(10/13/22 19:12:02)	(Detached)
	330290.alexios	(10/13/22 18:49:29)	(Detached)
	326263.upgrade	(10/13/22 10:06:39)	(Detached)
3 Sockets in /run/screen/S-hans.
sample output:
        */
        array(
            0 => array(
                'id' => '330901',
                'name' => 'screen with spaces',
                'creation_date' => '10/13/22 19:12:03',
                'attachment_status' => 'Detached',
            ),
            1 => array(
                'id' => '330290',
                'name' => 'alexios',
                'creation_date' => '10/13/22 18:49:30',
                'attachment_status' => 'Detached',
            ),
            2 => array(
                'id' => '326263',
                'name' => 'upgrade',
                'creation_date' => '10/13/22 10:06:40',
                'attachment_status' => 'Detached',
            ),
        );
        $screenList = [];
        $screenListRaw = shell_exec('screen -ls');
        preg_match_all('/^\t(?<screen_id>\d+)\.(?<screen_name>[^\t]+)\t\((?<creation_date>[^\)]+)\)\t\((?<attachment_status>[^\)]+)\)$/m', $screenListRaw, $matches);
        foreach ($matches['screen_id'] as $key => $id) {
            $screenList[] = [
                'id' => $id,
                'name' => $matches['screen_name'][$key],
                'creation_date' => $matches['creation_date'][$key],
                'attachment_status' => $matches['attachment_status'][$key],
            ];
        }
        return $screenList;
    }
    public static function isScreenRunning(string $screenName): bool
    {
        $screenList = self::getScreenList();
        foreach ($screenList as $screen) {
            if ($screen['name'] === $screenName) {
                return true;
            }
        }
        return false;
    }
    public static function getTerminalText(string $screenName): string
    {
        $tmph = tmpfile();
        $tmpf = stream_get_meta_data($tmph)['uri'];
        shell_exec("screen -S " . escapeshellarg($screenName) . " -X hardcopy " . escapeshellarg($tmpf));
        $screenText = file_get_contents($tmpf);
        fclose($tmph);
        return $screenText;
    }
    public static function createScreenSession(string $screenName): void
    {
        shell_exec("screen -S " . escapeshellarg($screenName) . " -d -m");
        usleep(1000); // wait 1ms, workaround https://savannah.gnu.org/bugs/?54164
    }
    public static function sendToScreen(string $screenName, string $text, bool $pressEnter = true): void
    {
        if ($pressEnter) {
            $text .= "^M"; // contrary to popular belief, \n is not pressing enter, but ^M is.
        }
        shell_exec("screen -S " . escapeshellarg($screenName) . " -X stuff " . escapeshellarg($text));
    }
}
