<?php
ini_set('display_errors', 'stderr');

if ($argc == 1){
    Parse();
    exit(0);
}
elseif ($argc == 2){
    if ($argv[1] == "--help"){
        echo("Help:\n\tparser.php [optional paramether] <inputFile >outputFile\n\nOptional paramether:\n\t--help\t-shows tooltip\n\nShort description:\n\tFilter script reads from STDIN source code in language IPPcode22,checks lexical and syntactic correctness of the code and outputs to STDOUT XML representation of the program.\n");
        exit (0); //OK
    }

    exit (10); // wrong paramether
    
}
else{
    exit(10); // wrong number of paramethers
}

// removes comments and multiple white spaces from line
function RemComAndSpaces(&$line){
    $line = trim($line);                       //removes white spaces from sides
    $line = preg_replace('/\s+/', ' ', $line); //removes multiple white spaces and replaces them with one space white space
    $line = explode('#', $line);
    $line = $line[0];                          //discarding comment part
    $line = trim($line);                       //removes white spaces from sides
}

// appending output XML
function appendXML(&$XML, $string){
    $XML = $XML.$string;
}

// parsing input from STDIN line by line and if successfull printing XML to STDOUT
function Parse(){

    $instructionNum = 0;
    $XML = "";
    while ($line = fgets(STDIN)){              //reading line by line
       
        RemComAndSpaces($line);
        if(strlen($line) == 0) continue;           //ignoring empty lines
       
        //echo "$line\n";     //test purposes
       
        //checking if the first instruction is a valid header
        if ($instructionNum == 0){
            if ($line == ".IPPcode22"){
                appendXML($XML, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
                appendXML($XML, "<program language=\"IPPcode22\">\n");
                /* echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
                   echo("<program language=\"IPPcode22\">\n");*/
                $instructionNum++;
                continue;
            }

            exit(21); // no valid header error
        }

        $words = explode(' ', $line);        //breaking the line into array of separate words

        switch(strtoupper($words[0])){
            
            //OPCODE ⟨var⟩ ⟨symb⟩
            case 'MOVE':
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
                if(count($words) != 3){ exit(23); } //incorrect number of paramethers for OPcode
                
                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[1])){  // checking ⟨var⟩ syntax
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    appendXML($XML, "\t\t<arg1 type=\"var\">$words[1]</arg1>\n");
                    /*echo("\t\t<arg1 type=\"var\">$words[1]</arg1>\n");*/
                   
                    $symbol = explode("@",$words[2]);
                    
                    if(count($symbol) != 2){ exit(23); } //incorrect symbol syntax
                    
                    $symbolFrameOrType = $symbol[0];  //before @
                    $symbolName = $symbol[1];         //after @
                    
                    if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[2])){  // checking ⟨symb⟩ syntax if is <var>
                       // echo("Var\n");
                       $words[2] = preg_replace('/&/', '&amp;', $words[2]);
                       $words[2] = preg_replace('/</', '&lt;', $words[2]);
                       $words[2] = preg_replace('/>/', '&gt;', $words[2]);
                       appendXML($XML, "\t\t<arg2 type=\"var\">$words[2]</arg2>\n");
                       /* echo("\t\t<arg2 type=\"var\">$words[2]</arg2>\n");*/
                    }
                    elseif (preg_match("/^(bool)@(true|false)$/", $words[2])){                         //checking if bool
                        //echo("Bool\n");
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                       /* echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                    }
                    elseif (preg_match("/^(nil)@(nil)$/", $words[2])){                                //checking if nil
                       // echo("nil\n");
                       appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                    }
                    elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[2])){                            //checking if int
                        //echo("int\n");
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                    }
                    //elseif (preg_match("/^string@([a-zA-Z0-9\x7f-\xff\-_$<>&\/%*!?]|\\\[0-9]{3})*$/", $words[2])){     //checking if string 
                    elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[2])){                                     
                        $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                        $symbolName = preg_replace('/</', '&lt;', $symbolName);
                        $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                    }
                    else{
                        exit(23);     //invalid <symb> syntax
                    }
                    appendXML($XML, "\t</instruction>\n");
                   /* echo("\t</instruction>\n");   //end of instruction
                   */

                }else{
                    exit(23);
                }
                
                break;

            //OPCODE
            case 'CREATEFRAME':
            case 'PUSHFRAME':
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':

                if(count($words) != 1){ exit(23); } //incorrect number of paramethers for OPcode

              /*  echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                echo("\t</instruction>\n");   //end of instruction
                */
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                appendXML($XML, "\t</instruction>\n");
                break;

            //OPCODE <var>
            case 'DEFVAR':
            case 'POPS':

                if(count($words) != 2){ exit(23); } //incorrect number of paramethers for OPcode

                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[1])){  // checking ⟨var⟩ syntax
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                   /* echo("\t\t<arg1 type=\"var\">$words[1]</arg1>\n"); */
                    appendXML($XML, "\t\t<arg1 type=\"var\">$words[1]</arg1>\n");
                }else{
                    exit(23);
                }

                /*echo("\t</instruction>\n");   //end of instruction */
                appendXML($XML, "\t</instruction>\n");

                break;

            //OPCODE <LABEL>  
            case 'CALL':  
            case 'LABEL':
            case 'JUMP':

                if(count($words) != 2){ exit(23); } //incorrect number of paramethers for OPcode

                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^[a-zA-Z\-_$&%*!?][a-zA-Z\-_$&%*!?0-9]*$/", $words[1])){  //checking <LABEL> syntax
                    /*echo("\t\t<arg1 type=\"label\">$words[1]</arg1>\n");*/
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    appendXML($XML, "\t\t<arg1 type=\"label\">$words[1]</arg1>\n");
                }else{
                    exit(23);
                }

                /*echo("\t</instruction>\n");   //end of instruction */
                appendXML($XML, "\t</instruction>\n");
                break;

            //OPCODE <symb>   
            case 'WRITE':
            case 'EXIT':
            case 'DPRINT':
            case 'PUSHS':
                
                if(count($words) != 2){ exit(23); } //incorrect number of paramethers for OPcode

                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                $symbol = explode("@",$words[1]);
                    
                if(count($symbol) != 2){ exit(23); } //incorrect symbol syntax
                
                $symbolFrameOrType = $symbol[0];  //before @
                $symbolName = $symbol[1];         //after @

                if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[1])){  // checking ⟨symb⟩ syntax if is <var>
                    /*echo("\t\t<arg1 type=\"var\">$words[1]</arg1>\n");*/
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    appendXML($XML, "\t\t<arg1 type=\"var\">$words[1]</arg1>\n");
                }
                elseif (preg_match("/^(bool)@(true|false)$/", $words[1])){                         //checking if bool
                    /*echo("\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");*/
                    appendXML($XML, "\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");
                }
                elseif (preg_match("/^(nil)@(nil)$/", $words[1])){                                //checking if nil
                    /*echo("\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");*/
                    appendXML($XML, "\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");
                }
                elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[1])){                            //checking if int
                   /* echo("\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");*/
                    appendXML($XML, "\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");
                }
                elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[1])){    //checking if string
                    $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                    $symbolName = preg_replace('/</', '&lt;', $symbolName);
                    $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                    /*echo("\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");*/
                    appendXML($XML, "\t\t<arg1 type=\"$symbolFrameOrType\">$symbolName</arg1>\n");
                }
                else{
                    exit(23);     //invalid <symb> syntax
                }

               /* echo("\t</instruction>\n");   //end of instruction */
                appendXML($XML, "\t</instruction>\n");
                
                break;


            //OPCODE <var> <symb> <symb>
            case 'ADD':
            case 'SUB':
            case 'MUL':
            case 'IDIV':
            case 'LT':
            case 'GT':
            case 'EQ':                           
            case 'AND':
            case 'OR':
            case 'NOT':
            case 'STRI2INT':
            case 'CONCAT':
            case 'GETCHAR':
            case 'SETCHAR':
            
                if(count($words) != 4){ exit(23); } //incorrect number of paramethers for OPcode
                
                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[1])){  // checking ⟨var⟩ syntax
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    /*echo("\t\t<arg1 type=\"var\">$words[1]</arg1>\n");*/
                    appendXML($XML, "\t\t<arg1 type=\"var\">$words[1]</arg1>\n");
                    //first symbol part
                    $symbol = explode("@",$words[2]);
                    
                    if(count($symbol) != 2){ exit(23); } //incorrect symbol syntax
                    
                    $symbolFrameOrType = $symbol[0];  //before @
                    $symbolName = $symbol[1];         //after @
                    
                    if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[2])){  // checking ⟨symb⟩ syntax if is <var>
                        /*echo("\t\t<arg2 type=\"var\">$words[2]</arg2>\n");*/
                        $words[2] = preg_replace('/&/', '&amp;', $words[2]);
                        $words[2] = preg_replace('/</', '&lt;', $words[2]);
                        $words[2] = preg_replace('/>/', '&gt;', $words[2]);
                        appendXML($XML, "\t\t<arg2 type=\"var\">$words[2]</arg2>\n");
                    }
                    elseif (preg_match("/^(bool)@(true|false)$/", $words[2])){                         //checking if bool
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^(nil)@(nil)$/", $words[2])){                                //checking if nil
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[2])){                            //checking if int
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[2])){     //checking if string
                        $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                        $symbolName = preg_replace('/</', '&lt;', $symbolName);
                        $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    else{
                        exit(23);     //invalid <symb> syntax
                    }

                    //second <symbol> part
                    $symbol2 = explode("@",$words[3]);
                    
                    if(count($symbol2) != 2){ exit(23); } //incorrect symbol syntax
                    
                    $symbolFrameOrType2 = $symbol2[0];  //before @
                    $symbolName2 = $symbol2[1];         //after @
                    
                    if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[3])){  // checking ⟨symb⟩ syntax if is <var>
                        /*echo("\t\t<arg3 type=\"var\">$words[3]</arg3>\n");*/
                        $words[3] = preg_replace('/&/', '&amp;', $words[3]);
                        $words[3] = preg_replace('/</', '&lt;', $words[3]);
                        $words[3] = preg_replace('/>/', '&gt;', $words[3]);
                        appendXML($XML, "\t\t<arg3 type=\"var\">$words[3]</arg3>\n");
                    }
                    elseif (preg_match("/^(bool)@(true|false)$/", $words[3])){                         //checking if bool
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^(nil)@(nil)$/", $words[3])){                                //checking if nil
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[3])){                            //checking if int
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[3])){     //checking if string
                        $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                        $symbolName = preg_replace('/</', '&lt;', $symbolName);
                        $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                       /* echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    else{
                        exit(23);     //invalid <symb> syntax
                    }

                    /*echo("\t</instruction>\n");   //end of instruction*/
                    appendXML($XML, "\t</instruction>\n");

                }else{
                    exit(23);
                }

                break;

            //OPcode ⟨var⟩ ⟨type⟩    
            case 'READ':

                if(count($words) != 3){ exit(23); } //incorrect number of paramethers for OPcode
                
                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[1])){  // checking ⟨var⟩ syntax
                    /*echo("\t\t<arg1 type=\"var\">$words[1]</arg1>\n");*/
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    appendXML($XML, "\t\t<arg1 type=\"var\">$words[1]</arg1>\n");
                }else{
                    exit(23);
                }

                if (preg_match("/^(bool|int|string)$/", $words[2])){
                    /*echo("\t\t<arg2 type=\"type\">$words[2]</arg2>\n");*/
                    appendXML($XML, "\t\t<arg2 type=\"type\">$words[2]</arg2>\n");
                }else{
                    exit(23);
                }

               /* echo("\t</instruction>\n");   //end of instruction*/
                appendXML($XML, "\t</instruction>\n");
                break;

            //OPCODE <label> <symb> <symb>   
            case 'JUMPIFEQ':
            case 'JUMPIFNEQ':
                      
                if(count($words) != 4){ exit(23); } //incorrect number of paramethers for OPcode
                
                /*echo("\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");*/
                appendXML($XML, "\t<instruction order=\"$instructionNum\" opcode=\"".strtoupper($words[0])."\">\n");
                if (preg_match("/^[a-zA-Z\-_$&%*!?][a-zA-Z\-_$&%*!?0-9]*$/", $words[1])){  //checking <LABEL> syntax
                    
                    /*echo("\t\t<arg1 type=\"label\">$words[1]</arg1>\n");*/
                    $words[1] = preg_replace('/&/', '&amp;', $words[1]);
                    $words[1] = preg_replace('/</', '&lt;', $words[1]);
                    $words[1] = preg_replace('/>/', '&gt;', $words[1]);
                    appendXML($XML, "\t\t<arg1 type=\"label\">$words[1]</arg1>\n");
                    //first symbol part
                    $symbol = explode("@",$words[2]);
                    
                    if(count($symbol) != 2){ exit(23); } //incorrect symbol syntax
                    
                    $symbolFrameOrType = $symbol[0];  //before @
                    $symbolName = $symbol[1];         //after @
                    
                    if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[2])){  // checking ⟨symb⟩ syntax if is <var>
                        /*echo("\t\t<arg2 type=\"var\">$words[2]</arg2>\n");*/
                        $words[2] = preg_replace('/&/', '&amp;', $words[2]);
                        $words[2] = preg_replace('/</', '&lt;', $words[2]);
                        $words[2] = preg_replace('/>/', '&gt;', $words[2]);
                        appendXML($XML, "\t\t<arg2 type=\"var\">$words[2]</arg2>\n");
                    }
                    elseif (preg_match("/^(bool)@(true|false)$/", $words[2])){                         //checking if bool
                       /* echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^(nil)@(nil)$/", $words[2])){                                //checking if nil
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[2])){                            //checking if int
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[2])){     //checking if string
                        $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                        $symbolName = preg_replace('/</', '&lt;', $symbolName);
                        $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                        /*echo("\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");*/
                        appendXML($XML, "\t\t<arg2 type=\"$symbolFrameOrType\">$symbolName</arg2>\n");
                    }
                    else{
                        exit(23);     //invalid <symb> syntax
                    }

                    //second <symbol> part
                    $symbol2 = explode("@",$words[3]);
                    
                    if(count($symbol2) != 2){ exit(23); } //incorrect symbol syntax
                    
                    $symbolFrameOrType2 = $symbol2[0];  //before @
                    $symbolName2 = $symbol2[1];         //after @
                    
                    if (preg_match("/^(LF|TF|GF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*$/", $words[3])){  // checking ⟨symb⟩ syntax if is <var>
                        /*echo("\t\t<arg3 type=\"var\">$words[3]</arg3>\n");*/
                        $words[3] = preg_replace('/&/', '&amp;', $words[3]);
                        $words[3] = preg_replace('/</', '&lt;', $words[3]);
                        $words[3] = preg_replace('/>/', '&gt;', $words[3]);
                        appendXML($XML, "\t\t<arg3 type=\"var\">$words[3]</arg3>\n");
                    }
                    elseif (preg_match("/^(bool)@(true|false)$/", $words[3])){                         //checking if bool
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^(nil)@(nil)$/", $words[3])){                                //checking if nil
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^(int)@\+?\-?\d+$/", $words[3])){                            //checking if int
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    elseif (preg_match("/^string@([\x{0021}\x{0022}\x{0024}-\x{005b}\x{005d}-\x{ffff}]|\\\[0-9]{3})*$/u", $words[3])){     //checking if string
                        $symbolName = preg_replace('/&/', '&amp;', $symbolName);
                        $symbolName = preg_replace('/</', '&lt;', $symbolName);
                        $symbolName = preg_replace('/>/', '&gt;', $symbolName);
                        /*echo("\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");*/
                        appendXML($XML, "\t\t<arg3 type=\"$symbolFrameOrType2\">$symbolName2</arg3>\n");
                    }
                    else{
                        exit(23);     //invalid <symb> syntax
                    }

                    /*echo("\t</instruction>\n");   //end of instruction*/
                    appendXML($XML, "\t</instruction>\n");

                }else{
                    exit(23);
                }

                break;

            default:
               
                exit(22); //unedintified OPcode
                break;    //good practice i guess :)
        }
        $instructionNum++;
    }

    /*echo("</program>\n"); //final XML tag*/
    appendXML($XML, "</program>\n");

    echo("$XML"); //if whole code got parsed successfully, print out XML

}
?>
