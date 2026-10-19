<?php

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Entities\IMail;

interface IExtension
{
    /**
     * @deprecated in 2.0 extensions process rendered mail instead of decorating it, see UPGRADE-2.0.md
     */
    public function extend(IMail $mail): IMail;
}
