<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects info about PHP
 */
class FilesCollector extends DataCollector implements Renderable, AssetProvider
{
    public $regex = '/(?:include|require)(?:_once)?\s*(?:\(?\s*([\'"][^\'"]+[\'"])\s*\)?)/im';

    private $repo_root = false;
    private $git_collector = true;
    private $git_html = true;

    /**
     * @return string
     */
    public function getName()
    {
        return 'files';
    }

    /**
     * @return array
     */
    public function collect()
    {
        $excludeCount = 0;
        $excluded = array(
            'Librerias/',
            'DATA/libs/',
            'DATA/GestorErrores.php',
        );
        $files = array();
        $self = $this;
        $dir = realpath(__DIR__ . '/../../../../../').DIRECTORY_SEPARATOR;
        $this->findGitRoot($dir);
        $git_html = $this->git_html;
        $git_collector = $this->git_collector;

        try{
            $files = array_map(function ($file) use ($self, $git_collector, $git_html) {
                $temp=array(
                    'name' => $self->normalizeFilePath($file),
                    'xdebug_link' => $self->getXdebugLink($file),
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                    'memory_str' => $self->getDataFormatter()->formatBytes(filesize($file)),
                    'render_time_str' => substr(''.decoct(fileperms($file)),-4)
                );
                if($calls = $self->getIncludedFiles($file)) {
                    if($calls['class'])
                        $temp['params'] = array('Class Name' => $calls['class']);
                    if(count($calls['files'])){
                        if(!isset($temp['params'])) $temp['params'] = array();
                        $temp['param_count'] = count($calls['files']);
                        $temp['params']['Includes/Requires'] = '<pre>'.implode('<br>',$calls['files']).'</pre>';
                    }
                }
                if ($git_collector && $gits = $self->getGitFileInfo($file, $git_html)) {
                    $icon = !$git_html? '» ' : '<i class="phpdebugbar-fa-brands phpdebugbar-fa-github" style="color:gray;" title="Git Info"></i> ';
                    $temp['html'] = $temp['name'] . ' '. $self->getGitFileChanged($file, $git_html) . $icon . $gits[0];
                    if(!isset($temp['params'])) $temp['params'] = array();
                    $temp['params']['Git History'] = implode("\n", $gits);
                }

                return $temp;
            },array_values(array_filter(get_included_files(), function ($file) use ($excluded, $dir, &$excludeCount) {
                foreach($excluded as $path){
                    if(strpos($file, str_replace('/', DIRECTORY_SEPARATOR, $dir.$path)) === 0) {
                        $excludeCount++;
                        return false;
                    }
                }
                return true;
            })));
        }catch(\Exception $e){ unset($e); }

        $count = count($files);
        return array(
            'count' => $count,
            'nb_templates' => $count,
            'templates' => $files,
            'sentence' => 'Files were loaded' . (!$excludeCount ? '' : ", {$excludeCount} files were excluded from this list")
        );
    }

    public function getIncludedFiles($file) {
        try{
            $file_content = @file_get_contents($file);
            if ($file_content === false) return null;

            $class = null;
            $calls = array();
            $matches = array();
            $dir = dirname($file);

            if (preg_match_all($this->regex, $file_content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    // $match[0] contendrá la línea completa que coincidió (ej: include "ruta/a/mi_archivo.php";)
                    // $match[1] contendrá la parte capturada, que es la cadena de la ruta (ej: "ruta/a/mi_archivo.php")
                    $ruta_cruda = $match[1];

                    // Limpiar las comillas de la ruta
                    $ruta_limpia = str_replace('/', DIRECTORY_SEPARATOR, trim($ruta_cruda, '\'"')); // Quita comillas simples o dobles de los extremos
                    $ruta_real = @realpath($dir.'/'.$ruta_limpia);

                    $calls[] = htmlspecialchars(is_string($ruta_real) ? $this->normalizeFilePath($ruta_real) : $ruta_limpia);
                }
            }
            $calls = array_unique($calls);

            try {
                if (preg_match('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*(?:extends\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)?\s*\{/i', $file_content, $matches))
                    $class = $matches[1];
            }catch(\Exception $e0){}

            return count($calls) || $class ? array('files'=>$calls, 'class'=>$class) : null;
        }catch(\Exception $e){
            return null;
        }
    }

    public function getGitFileChanged($file_path, $html = false) {
        if (!$this->repo_root) {
            return null; // No es un repositorio Git o no se pudo encontrar la raíz
        }

        $changed = false;
        try{ $changed = shell_exec('cd ' . escapeshellarg($this->repo_root) . ' && (git diff --quiet HEAD -- ' . escapeshellarg($file_path) . ' || echo true)'); }catch(\Exception $e){unset($e);}

        return trim($changed) === 'true' ? ($html ? '<i class="phpdebugbar-far phpdebugbar-fa-clock" style="color:#77cf8e;" title="Editing..."></i> ': 'Editing.. ') : '';
    }

    public function getGitFileInfo($file_path, $html = false) {
        if (!$this->repo_root) {
            return null; // No es un repositorio Git o no se pudo encontrar la raíz
        }

        $output = null;
        $format = !$html  // %h = commit hash, %an = author name, %ad = author date, %s = subject (commit message)
            ?'[%h - (%ad)&lt;%an&gt; %s]'
            : "<span style='color:red;'>%h</span> - <span style='color:green;'>(%ad)</span><span style='color:coral;'>&lt;%an&gt;</span> %s";
        $command = 'cd ' . escapeshellarg($this->repo_root) . ' && git log -5 --format="'.$format.'" --date=relative -- ' . escapeshellarg($file_path);

        try{$output = shell_exec($command);}catch(\Exception $e){unset($e);}

        return empty($output) ? null : explode("\n", $output);
    }

    protected function findGitRoot($path) {
        try{
            $current_path = rtrim($path, DIRECTORY_SEPARATOR);
            while (true) {
                if (is_dir($current_path . DIRECTORY_SEPARATOR . '.git')) {
                    $this->repo_root = $current_path;
                    break;
                }
                $parent_path = dirname($current_path);
                if ($parent_path === $current_path) { // Llegamos a la raíz del sistema de archivos
                    break;
                }
                $current_path = $parent_path;
            }
        }catch(\Exception $e){unset($e);}
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            'files' => array(
                'icon' => 'leaf',
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => 'files',
                'default' => '[]'
            ),
            'files:badge' => array(
                'map' => 'files.nb_templates',
                'default' => 0
            )
        );
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return array(
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        );
    }



    protected $xdebugLinkTemplate = '';
    protected $xdebugShouldUseAjax = false;
    protected $xdebugReplacements = array();

    /**
     * Shorten the file path by removing the xdebug path replacements
     *
     * @param string $file
     * @return string
     */
    public function normalizeFilePath($file)
    {
        if (empty($file)) {
            return '';
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        foreach (array_keys($this->xdebugReplacements) as $path) {
            if (strpos($file, $path) === 0) {
                $file = substr($file, strlen($path));
                break;
            }
        }

        return ltrim(str_replace('\\', '/', $file), '/');
    }

    /**
     * Get an Xdebug Link to a file
     *
     * @param string $file
     * @param int|null $line
     *
     * @return array {
     * @var string   $url
     * @var bool     $ajax should be used to open the url instead of a normal links
     * }
     */
    public function getXdebugLink($file, $line = null)
    {
        if (empty($file)) {
            return null;
        }

        if (@file_exists($file)) {
            $file = realpath($file);
        }

        foreach ($this->xdebugReplacements as $path => $replacement) {
            if (strpos($file, $path) === 0) {
                $file = $replacement . substr($file, strlen($path));
                break;
            }
        }

        $url = strtr($this->getXdebugLinkTemplate(), array(
            '%f' => rawurlencode(str_replace('\\', '/', $file)),
            '%l' => rawurlencode((string) $line ?: 1),
        ));
        if ($url) {
            return array(
                'url' => $url,
                'ajax' => $this->getXdebugShouldUseAjax(),
                'filename' => basename($file),
                'line' => (string) $line ?: '?'
            );
        }
    }

    /**
     * @return string
     */
    public function getXdebugLinkTemplate()
    {
        if (empty($this->xdebugLinkTemplate)) {
            $ini = ini_get('xdebug.file_link_format');
            if (!empty($ini))
                $this->xdebugLinkTemplate = ini_get('xdebug.file_link_format');
        }

        return $this->xdebugLinkTemplate;
    }

    /**
     * @param string $editor
     */
    public function setEditorLinkTemplate($editor)
    {
        $editorLinkTemplates = array(
            'sublime' => 'subl://open?url=file://%f&line=%l',
            'textmate' => 'txmt://open?url=file://%f&line=%l',
            'emacs' => 'emacs://open?url=file://%f&line=%l',
            'macvim' => 'mvim://open/?url=file://%f&line=%l',
            'codelite' => 'codelite://open?file=%f&line=%l',
            'phpstorm' => 'phpstorm://open?file=%f&line=%l',
            'phpstorm-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;' .
                'r.open(\'get\',\'http://localhost:63342/api/file/%f:%l\');r.send();})()',
            'idea' => 'idea://open?file=%f&line=%l',
            'idea-remote' => 'javascript:(()=>{let r=new XMLHttpRequest;' .
                'r.open(\'get\',\'http://localhost:63342/api/file/?file=%f&line=%l\');r.send();})()',
            'vscode' => 'vscode://file/%f:%l',
            'vscode-insiders' => 'vscode-insiders://file/%f:%l',
            'vscode-remote' => 'vscode://vscode-remote/%f:%l',
            'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%f:%l',
            'vscodium' => 'vscodium://file/%f:%l',
            'nova' => 'nova://open?path=%f&line=%l',
            'xdebug' => 'xdebug://%f@%l',
            'atom' => 'atom://core/open/file?filename=%f&line=%l',
            'espresso' => 'x-espresso://open?filepath=%f&lines=%l',
            'netbeans' => 'netbeans://open/?f=%f:%l',
            'cursor' => 'cursor://file/%f:%l',
        );

        if (is_string($editor) && isset($editorLinkTemplates[$editor])) {
            $this->setXdebugLinkTemplate($editorLinkTemplates[$editor]);
        }
    }

    /**
     * @param string $xdebugLinkTemplate
     * @param bool $shouldUseAjax
     */
    public function setXdebugLinkTemplate($xdebugLinkTemplate, $shouldUseAjax = false)
    {
        if ($xdebugLinkTemplate === 'idea') {
            $this->xdebugLinkTemplate  = 'http://localhost:63342/api/file/?file=%f&line=%l';
            $this->xdebugShouldUseAjax = true;
        } else {
            $this->xdebugLinkTemplate  = $xdebugLinkTemplate;
            $this->xdebugShouldUseAjax = $shouldUseAjax;
        }
    }

    /**
     * @return bool
     */
    public function getXdebugShouldUseAjax()
    {
        return $this->xdebugShouldUseAjax;
    }

    /**
     * returns an array of filename-replacements
     *
     * this is useful f.e. when using vagrant or remote servers,
     * where the path of the file is different between server and
     * development environment
     *
     * @return array key-value-pairs of replacements, key = path on server, value = replacement
     */
    public function getXdebugReplacements()
    {
        return $this->xdebugReplacements;
    }

    /**
     * @param array $xdebugReplacements
     */
    public function addXdebugReplacements($xdebugReplacements)
    {
        foreach ($xdebugReplacements as $serverPath => $replacement) {
            $this->setXdebugReplacement($serverPath, $replacement);
        }
    }

    /**
     * @param array $xdebugReplacements
     */
    public function setXdebugReplacements($xdebugReplacements)
    {
        $this->xdebugReplacements = $xdebugReplacements;
    }

    /**
     * @param string $serverPath
     * @param string $replacement
     */
    public function setXdebugReplacement($serverPath, $replacement)
    {
        $this->xdebugReplacements[$serverPath] = $replacement;
    }
}
