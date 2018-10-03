SymlinkDependencies Composer extension
======================================

This composer extension allows developper to install a local package with symlink instead of using github repository.
This can be usefull for test many app with same dev-bundle update without installing many time a bundle.

HOWTO INSTALL
-------------

.. code:: bash

    $ composer global require no_obu/symlink-dependencies

bundle.map.php format
.....................

.. code:: php

    <?php

    return [
        'vendor1/packageName1' => '/absolute/path/to/directory/bunde1',
        'vendor2/packageName2' => '/absolute/path/to/directory/bunde2',
        ...
        'vendorN/packageNameN' => '/absolute/path/to/directory/bundeN',
    ];

