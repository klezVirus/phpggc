<?php

namespace PHPGGC;

/**
 * Class handling the generation of a gadget chain specific to a PHP CMS,
 * framework, or library.
 * The class will automatically include the chain.php file present in the same
 * directory.
 *
 * Upon calling generate(), this object produces a gadget chain object.
 * The object can then be serialize()d into a serialized string.
 * Calling unserialize() on the string, in the right environment, should produce
 * an action: execute a PHP function, write a file, delete a file, etc.
 *
 * Depending on the type of the chain, some parameters must be given. For
 * instance, an RCE Gadget Chain generally requires the name of a function to
 * execute, along with its parameter(s).
 * A file write gadget chain requires the path of the remote file we wish to
 * write, and the path of a local file whose content is to be written.
 *
 * Along with the generate() method, which converts parameters into an object,
 * three generic methods are available:
 * - process_parameters($parameters)
 * - process_object($object)
 * - process_serialized($serialized)
 *
 * Those methods are to be found in other PHPGGC classes, for instance the main
 * class for handling CLI, PHPGGC. Refer to their documentation to understand
 * their usage.
 */
abstract class GadgetChain
{
    public $name;
    public static $type;
    public static $version = '?';
    # Vector to start the chain: __destruct, __toString, offsetGet, etc.
    public static $vector = '';
    public static $author = '';
    public static $parameters = [];
    public static $informations;

    # Types
    const TYPE_RCE = 'rce';
    const TYPE_CMD = 'cmd_injection';
    const TYPE_FI = 'file_include';
    const TYPE_FR = 'file_read';
    const TYPE_FW = 'file_write';
    const TYPE_FD = 'file_delete';
    const TYPE_SQLI = 'sql_injection';
    const TYPE_INFO = 'phpinfo()';

    function __construct()
    {
        $this->load_gadgets();
    }

    protected function load_gadgets()
    {
        $directory = dirname((new \ReflectionClass($this))->getFileName());
        require_once $directory . '/gadgets.php';
    }

    /**
     * Generates the gadget chain object from given parameters.
     * Parameters are expected to have been processed before.
     *
     * @param array $parameters Gadget chain parameters
     * @return Object
     */
    abstract public function generate(array $parameters);

    /**
     * Modifies given parameters if required.
     * Called before `generate()`.
     * This is called on the gadget chain's parameters, such as for instance
     * "remote_file" and "local_file" for a file write chain.
     *
     * @param array $parameters Gadget chain parameters
     * @return array Modified parameters
     */
    public function process_parameters(array $parameters)
    {
        return $parameters;
    }

    /**
     * Modifies the object generated by this class if required.
     * Called after the object has been generated using `generate()`, and before
     * `serialize()`.
     *
     * One of the main usages is to convert given object into something that can
     * be processed by the targeted system.
     * @param Object $parameters Gadget chain object
     * @return Object Modified object
     */
    public function process_object($object)
    {
        return $object;
    }

    /**
     * Modifies given serialized string if required.
     * Called after `serialize()`.
     * For instance, if a class is meant to be named A\B\C but has been named
     * A_B_C in the gadget for convenience, it can be str_replace()d here.
     *
     * @param string $serialized Serialized string representing the gadget chain
     * @return string Modified serialized string
     */
    public function process_serialized($serialized)
    {
        return $serialized;
    }

    /**
     * Returns a string describing the gadget chain.
     */
    public function __toString()
    {
        $infos = [
            'Name' => static::get_name(),
            'Version' => static::$version,
            'Type' => static::$type,
            'Vector' => static::$vector
        ];

        $strings = [];

        if(static::$informations)
        {
            $informations = trim(static::$informations);
            $informations = preg_replace("#\n\s+#", "\n", $informations);
            $infos['Informations'] = "\n" . $informations;
        }

        foreach($infos as $k => $v)
        {
            $strings[] = str_pad($k, 15) . ': ' . $v;
        }

        return implode("\n", $strings);
    }

    /**
     * Returns a standard name for the gadget chain, generally of the form
     * <Framework>/<Type><N>, for instance Guzzle/RCE1.
     */
    public static function get_name()
    {
        $class = static::class;
        $class = substr($class, strpos($class, '\\') + 1);
        $class = str_replace('\\', '/', $class);
        return $class;
    }
}
