
use core\Config;
use core\Container;

class testModuleSite extends WeModuleSite
{
    public $filterParam = ['c','a','do','m','r','i'];

    public function __construct()
    {
        $this->register();
        $this->define();
        $this->config();
        $this->common();
    }

    public function doWebWeb()
    {
        global  $_GET;
        if(!$_GET['r']){
            $default_module = Container::get('config')->get('default_module');
            $_GET['r'] = $default_module;
        }
        $rule = explode('.',$_GET['r']);
        $module = $rule[0];
        $this->config($module); //加载模块配置文件
        if(!$rule[1]) $rule[1] =  Container::get('config')->get('default_controller');
        if(!$rule[2]) $rule[2] =  Container::get('config')->get('default_action');
        $aplication_namespace = Container::get('config')->get('application_namespace');

        //定义请求常量
        define('MODULE',$rule[0]);
        define('CONTROLLER',$rule[1]);
        define('ACTION',$rule[2]);
        define('NAMESPACE_NAME',$aplication_namespace);

        if(!$aplication_namespace) $aplication_namespace = 'app';

        $class = $aplication_namespace.'\\'.$rule[0].'\\controller'.'\\'.$rule[1];
        $param = $this->filterParam($_GET);
        $container =  new Container();
        $class = $container->make($class);

        $container->invokeMethod([$class,$rule[2]],$param);


    }

    /**
     * 注册自动加载加载
     */
    private function register()
    {
        //注册自动加载
        spl_autoload_register(function ($name){
            $file = __DIR__.DIRECTORY_SEPARATOR.$name.'.php';
            if(is_file($file)) include_once $file;
        });
    }

    /**
     * 加载配置
     */
    private function config($module = '')
    {
        $aplication_namespace = Container::get('config')->get('application_namespace');
        if(!$aplication_namespace) $aplication_namespace = 'app';

        if($module) $module = $aplication_namespace.DN.$module;

        $config = __DIR__.DN.'config';
        if($module){
            $config = __DIR__.DN.$module.DN.'config';
        }

        if(is_dir($config)){
            foreach (glob($config.DN.'*.php') as $name){
                $filename = pathinfo($name,PATHINFO_FILENAME);
                if($filename == 'config'){
                    Config::load($name);
                }else{
                    Config::load($name,$filename);
                }
            }
        }else{
            if(!$module) trigger_error('配置文件目录不存在'.$config,E_USER_ERROR);

        }

    }

    /**
     * 加载共用文件
     */
    public function common($name = '')
    {
        $file = __DIR__.DN.'common';
        if(!is_dir($file)) trigger_error('共用文件夹不存在'.$file,E_USER_ERROR);
        if(!$name){
            foreach (glob($file.DN.'*.php') as $file){
                require_once $file;
            }
        }else{
            $file = $file.DN.$name;
            if(!dir($file)) trigger_error('共用文件不存在'.$file,E_USER_ERROR);
            require_once $file;

        }

    }
    /**
     * 定义常量
     */
    public function define()
    {
            define('DN',DIRECTORY_SEPARATOR);
            define('ROOT',__DIR__);
    }

    /**
     * 过滤不要的请求参数
     */
    public function filterParam($param = [])
    {

        foreach ($this->filterParam as $name){
            if(isset($param[$name])){
                unset($param[$name]);
            }
        }

        return $param;
    }



}