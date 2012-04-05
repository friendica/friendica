<?php
//-----------------------------------------------------------------------------
// ASNValue class by A.Oliinyk
// contact@pumka.net
//-----------------------------------------------------------------------------
class ASNValue
{
    const TAG_INTEGER   = 0x02;
    const TAG_BITSTRING = 0x03;
    const TAG_SEQUENCE  = 0x30;
    
    public $Tag;
    public $Value;
    
    function __construct($Tag=0x00, $Value='')
    {
        $this->Tag = $Tag;
        $this->Value = $Value;
    }
    
    function Encode()
    {   
        //Write type
        $result = chr($this->Tag);

        //Write size
        $size = strlen($this->Value);
        if ($size < 127) {
            //Write size as is
            $result .= chr($size);
        }
        else {
            //Prepare length sequence
            $sizeBuf = self::IntToBin($size);

            //Write length sequence
            $firstByte = 0x80 + strlen($sizeBuf);
            $result .= chr($firstByte) . $sizeBuf;
        }

        //Write value
        $result .= $this->Value;
        
        return $result;
    }
    
    function Decode(&$Buffer)
    {   
        //Read type
        $this->Tag = self::ReadByte($Buffer);

        //Read first byte
        $firstByte = self::ReadByte($Buffer);  

        if ($firstByte < 127) {
            $size = $firstByte;
        }
        else if ($firstByte > 127) {
            $sizeLen = $firstByte - 0x80;
            //Read length sequence
            $size = self::BinToInt(self::ReadBytes($Buffer, $sizeLen));
        }
        else {
            throw new Exception("Invalid ASN length value");
        }

        $this->Value = self::ReadBytes($Buffer, $size);
    }
    
    protected static function ReadBytes(&$Buffer, $Length)
    {
        $result = substr($Buffer, 0, $Length);
        $Buffer = substr($Buffer, $Length);
        
        return $result;
    }
    
    protected static function ReadByte(&$Buffer)
    {      
        return ord(self::ReadBytes($Buffer, 1));
    }
    
    protected static function BinToInt($Bin)
    {    
        $len = strlen($Bin);
        $result = 0;
        for ($i=0; $i<$len; $i++) {
            $curByte = self::ReadByte($Bin);
            $result += $curByte << (($len-$i-1)*8);
        }
        
        return $result;
    }
    
    protected static function IntToBin($Int)
    {
        $result = '';
        do {
            $curByte = $Int % 256;
            $result .= chr($curByte);

            $Int = ($Int - $curByte) / 256;
        } while ($Int > 0);

        $result = strrev($result);
        
        return $result;
    }
    
    function SetIntBuffer($Value)
    {
        if (strlen($Value) > 1) {
            $firstByte = ord($Value{0});
            if ($firstByte & 0x80) { //first bit set
                $Value = chr(0x00) . $Value;
            }
        }
        
        $this->Value = $Value;
    }
    
    function GetIntBuffer()    
    {        
        $result = $this->Value;
        if (ord($result{0}) == 0x00) {
            $result = substr($result, 1);
        }
        
        return $result;
    }
    
    function SetInt($Value)
    {
        $Value = self::IntToBin($Value);
        
        $this->SetIntBuffer($Value);
    }   
    
    function GetInt()
    {
        $result = $this->GetIntBuffer();
        $result = self::BinToInt($result);
        
        return $result;
    }
    
    function SetSequence($Values)
    {
        $result = '';
        foreach ($Values as $item) {
            $result .= $item->Encode();            
        }   
        
        $this->Value = $result;
    }   
    
    function GetSequence()
    {
        $result = array();
        $seq = $this->Value;
        while (strlen($seq)) {
            $val = new ASNValue();
            $val->Decode($seq);
            $result[] = $val;
        }  
        
        return $result;
    }    
}
