<?php
header('Content-Type: text/plain; charset=utf-8');
$repo = '/home2/maisvi97/repositories/maisviagens';
echo "Iniciando deploy...
";
$p1 = shell_exec('git --git-dir=' . $repo . '/.git --work-tree=' . $repo . ' pull origin main 2>&1');
echo "Pull:
" . $p1 . "
";
$uapi = shell_exec('/usr/local/cpanel/bin/uapi VersionControlDeployment create repository_root=' . $repo . ' 2>&1');
if (!$uapi) {
    $uapi = shell_exec('/usr/bin/uapi VersionControlDeployment create repository_root=' . $repo . ' 2>&1');
}
echo "UAPI:
" . $uapi . "
";
$cp = shell_exec('cp -rf ' . $repo . '/* /home2/maisvi97/public_html/ 2>&1');
echo "Sync: " . ($cp ? $cp : "OK
");
echo "Deploy finalizado.
";
