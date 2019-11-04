<?php

if (!defined('ABSPATH')) { 
    exit; 
}

if( !class_exists( 'PR_DHL_Libraryloader' ) ){

    class PR_DHL_Libraryloader{


        const CLASS_PDF_MERGER = 'pdf_merger';

        private static $instances = array();

        protected $include_path = null;
        protected $file_path = null;
        protected $loaded = array();

        /**
         * Constructor.
         */
        public function __construct(){
            // Set paths
            $this->include_path     = PR_DHL_PLUGIN_DIR_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
            $this->file_path        = $this->include_path . 'PDFMerger' . DIRECTORY_SEPARATOR . 'PDFMerger.php';
            $this->file_path        = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->file_path);
        }

        /**
         * Returns a singleton instance of the called class
         * @return static
         */
        public static function instance(){

            $class = get_called_class();
            if (!isset(self::$instances[$class])) {
                self::$instances[$class] = new static();
            }
            return self::$instances[$class];
            
        }

        public function get_pdf_merger()
        {
            if (class_exists('PDFMerger') && !in_array(self::CLASS_PDF_MERGER, $this->loaded)) {
                // The class exists but we never loaded it. There's too high chance of a conflict
                // we have to exit. We are using PDFMerger 2.0 which works differently than 1.0
                // The site owner needs to check which plugin is trying to load PDFMerger without lazy loading
                // (can't imagine always loading a full on PDFMerger class without actually using PDF merges)
                return null;
            }
			
            if (!class_exists('PDFMerger')) { 
                $loaded = $this->include_file($this->file_path);

                if (!$loaded) {
                    return null;
                }
                $this->loaded[] = self::CLASS_PDF_MERGER;
            }

            if (!class_exists('PDFMerger')) {
                // Something very unexpected happened, return
                return null;
            }

            return new PDFMerger();
        }

        /**
         * Include a file if it exists.
         *
         * @param $path
         * @return bool
         */
        protected function include_file($path){
            
            if (file_exists($path)) {
                // Supress errors of third party libraries
                $status = @include_once $path;
                if ($status !== 1) {
                    return false;
                }
                return true;
            }
            return false;
        }
    }
}