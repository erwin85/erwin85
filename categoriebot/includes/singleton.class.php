<?php
class Singleton {

    /***********************
     * HOW TO USE
     *
     * Inherit(extend) from Singleton and add getter:
     *
     *  //public getter for singleton instance
     *     public static function getInstance(){
     *        return Singleton::getSingleton(get_class());
     *    }
     *
     */

    private static $instanceMap = array();

    //protected getter for singleton instances
    protected static function getSingleton($className){
        if(!isset(self::$instanceMap[$className])){

            $object = new $className;
            //Make sure this object inherit from Singleton
            if($object instanceof Singleton){
                self::$instanceMap[$className] = $object;
            }
            else{
                throw SingletonException("Class '$className' do not inherit from Singleton!");
            }
        }

        return self::$instanceMap[$className];
    }

    //protected constructor to prevent outside instantiation
    protected function __construct(){ }

    //denie cloning of singleton objects
    public final function __clone(){ }
}
?>
