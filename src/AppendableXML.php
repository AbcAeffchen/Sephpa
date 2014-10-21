<?php
/**
 * Project: Sephpa
 * User:    AbcAeffchen
 * Date:    21.10.2014
 */

namespace AbcAeffchen\Sephpa;


class AppendableXML extends \SimpleXMLElement
{
    /**
     * Add SimpleXMLElement code into a SimpleXMLElement
     *
     * @param \SimpleXMLIterator $append
     */
    public function appendXML(\SimpleXMLIterator $append)
    {
        for($append->rewind(); $append->valid(); $append->next())
        {
            if($append->hasChildren())
            {
                $xml = $this->addChild($append->key());
                $xml->appendXML($append->current());
            }
            else
            {
                $xml = $this->addChild($append->key(),(string) $append->current());
            }

            foreach($append->current()->attributes() as $name => $value)
            {
                $xml->addAttribute($name, $value);
            }
        }

//
//
//
//
//
//            if( $append )
//            {
//                if( strlen(trim((string) $append)) == 0 )       // $append has children
//                {
//                    //
//                    $xml = $this->addChild($append->getName());
//                    foreach($append->children() as $child)
//                    {
//                        $xml->appendXML($child);
//                    }
//                }
//                else
//                {
//                    $xml = $this->addChild($append->getName(), (string) $append);
//                }
//
//
//            }
    }
}