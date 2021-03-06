<?php

class CSVSICOPATExporter extends AbstarctExporter
{
    protected $CSVheaders = array();
    protected $parentNodeID;
    //protected $id_gruppo=0;
    private static $ERRORS_IN_FIELD = 'ERRORS_IN_FIELD';

    protected $count = 0;
    protected $values = array();
    protected $errors = array();
    protected $errorDescriptor = array();
    protected $anno_pubblicazione;

    private static $INVIATO = 'invitato';
    private static $PARTECIPANTE = 'partecipante';
    private static $AGGIUDICATARIO = 'aggiudicatario';

    public function __construct( $parentNodeID, $classIdentifier )
    {
        parent::__construct( $parentNodeID, $classIdentifier );
        $this->parentNodeID = $parentNodeID;

        //dati del parent (pagina trasparenza
        $parentNode = eZContentObjectTreeNode::fetch($this->parentNodeID);
        $parenObject = $parentNode->attribute( 'object' );
        $parent_data_map = $parenObject->dataMap();
        if($parent_data_map['anno_riferimento'])
        {
            $this->anno_pubblicazione = $parent_data_map['anno_riferimento']->content();
        }

        $this->createCSVHeader();
        $this->createErrorDescriptor();
    }

    private function createCSVHeader(){
        $this->CSVheaders = array('CIG','FLAG_CONTRATTO_SENZA_CIG','ANNO_PUBBLICAZIONE','OGGETTO','SCELTA_CONTRAENTE','IMPORTO_GARA','IMPORTO_AGGIUDICAZIONE','DATA_INIZIO','DATA_ULTIMAZIONE','IMPORTO_SOMME_LIQUIDATE','FLAG_COMPLETAMENTO','CF_AZIENDA','ID_GRUPPO','TIPO_PARTECIPAZIONE','ATTRIBUTO_INVITATA','ATRIBUTO_PARTECIPANTE','ATTRIBUTO_AGGIUDICATARIA');
    }

    function transformNode( eZContentObjectTreeNode $node )
    {

        set_time_limit(0);

        $row = array();
        $object = $node->attribute( 'object' );

        if(!$object instanceof eZContentObject)
        {
            return null;
        }

        $data_map = $object->dataMap();

        //0
        //CIG
        //cig nillable="false"
        $cig = self::$ERRORS_IN_FIELD;
        if ( $data_map['cig'] )
        {
            $cig = $data_map['cig']->content();

            //deve essere di 10 caratteri
            if(strlen($cig)!=10){
                $cig = self::$ERRORS_IN_FIELD;
            }
        }
        $row[] = $cig;

        //---------------------------------------------------------------------------
        //1
        //FLAG_CONTRATTO_SENZA_CIG
        if($cig){
            $row[] = 'N';
        }else{
            $row[] = 'S';
        }

        //---------------------------------------------------------------------------
        //2
        //ANNO_PUBBLICAZIONE
        //nillable="false
        $anno_pubblicazione = self::$ERRORS_IN_FIELD;
        if($this->anno_pubblicazione){
            $anno_pubblicazione = $this->anno_pubblicazione;

            if(strlen($anno_pubblicazione)!=4){
                $anno_pubblicazione = self::$ERRORS_IN_FIELD;
            }
        }
        $row[] = $anno_pubblicazione;

        //---------------------------------------------------------------------------
        //3
        //OGGETTO
        //oggetto nillable="false"
        $oggetto = '';
        if ( $data_map['oggetto'] )
        {
            $oggetto = $data_map['oggetto']->content();

            //xsd:maxLength value="500"
            if(strlen($oggetto)>500){
                $oggetto = self::$ERRORS_IN_FIELD;
            }
        }
        $row[] = $oggetto;

        //---------------------------------------------------------------------------
        //4
        //SCELTA_CONTRAENTE
        //sceltaContraente nillable="false" //select a scelta obbligata di valori indicati nella documentazione
        $scelta_contraente = self::$ERRORS_IN_FIELD;
        if ( $data_map['scelta_contraente'] )
        {
            if ( $data_map['scelta_contraente']->title())
            {
                $scelta_contraente = $data_map['scelta_contraente']->title();
                //tengo solo numeri come previsto da documentazione
                $scelta_contraente = substr($scelta_contraente, 0, 2);
            }
        }
        $row[] = $scelta_contraente;

        //---------------------------------------------------------------------------
        //5
        //IMPORTO_GARA
        $importo_gara = self::$ERRORS_IN_FIELD;
        if ( $data_map['importo_gara'] )
        {
            if ( $data_map['importo_gara']->content())
            {
                $importo_gara = $data_map['importo_gara']->content();
                if($importo_gara instanceof eZPrice){

                    if(!$this->checkImporti($importo_gara->Price)){
                        $importo_gara = self::$ERRORS_IN_FIELD;
                    }else{
                        $importo_gara = $importo_gara->Price;
                    }
                }else{
                    $importo_gara = self::$ERRORS_IN_FIELD;
                }
            }
        }
        $row[] = $importo_gara;

        //---------------------------------------------------------------------------
        //6
        //IMPORTO_AGGIUDICAZIONE
        $importo_aggiudicazione='';
        if ( $data_map['importo_aggiudicazione'] )
        {
            $importo_aggiudicazione = $data_map['importo_aggiudicazione']->content();

            if($importo_aggiudicazione instanceof eZPrice){
                if(!$this->checkImporti($importo_aggiudicazione->Price)){
                    $importo_aggiudicazione = self::$ERRORS_IN_FIELD;
                }else{
                    $importo_aggiudicazione = $importo_aggiudicazione->Price;
                }
            }else{
                $importo_aggiudicazione = self::$ERRORS_IN_FIELD;
            }
        }
        $row[] = $importo_aggiudicazione;

        //---------------------------------------------------------------------------
        //7
        //DATA_INIZIO
        $data_inizio = '';
        if ( $data_map['data_inizio'] )
        {
            $data_inizio = date( 'd/m/Y', $data_map['data_inizio']->DataInt );
        }
        $row[] = $data_inizio;

        //---------------------------------------------------------------------------
        //8
        //DATA_ULTIMAZIONE
        $data_ultimazione = '';
        if ( $data_map['data_ultimazione'] )
        {
            $data_ultimazione = date( 'd/m/Y', $data_map['data_ultimazione']->DataInt );
        }
        $row[] = $data_ultimazione;

        //---------------------------------------------------------------------------
        //9
        //IMPORTO_SOMME_LIQUIDATE
        $importo_somme_liquidate='';
        if ( $data_map['importo_somme_liquidate'] )
        {
            $importo_somme_liquidate = $data_map['importo_somme_liquidate']->content();

            if($importo_somme_liquidate instanceof eZPrice){
                if(!$this->checkImporti($importo_somme_liquidate->Price)){
                    $importo_somme_liquidate = self::$ERRORS_IN_FIELD;
                }else{
                    $importo_somme_liquidate = $importo_somme_liquidate->Price;
                }
            }else{
                $importo_somme_liquidate = self::$ERRORS_IN_FIELD;
            }
        }
        $row[] = $importo_somme_liquidate;

        //---------------------------------------------------------------------------
        //10
        //FLAG_COMPLETAMENTO
        $flag_completamento='';
        if ( $data_map['flag_completamento'] )
        {
            $flag_completamento = $data_map['flag_completamento']->content();
        }
        if($flag_completamento==1){
            $row[] = 'S';
        }else{
            $row[] = 'N';
        }

        //valorizzo i campi CF_AZIENDA;ID_GRUPPO;TIPO_PARTECIPAZIONE;ATTRIBUTO_INVITATA;ATRIBUTO_PARTECIPANTE;ATTRIBUTO_AGGIUDICATARIA
        //ripetendo le righe qualora ci siano più figure dello stesso tipo

        $anagrafiche = array();
        //$this->id_gruppo++;

        $invitati_matrix = $data_map['invitati']->content();
        $partecipanti_matrix = $data_map['partecipanti']->content();
        $aggiudicatario_matrix = $data_map['aggiudicatari']->content();

        $invitati_matrix_sequential = $invitati_matrix->Matrix['rows']['sequential'];
        $invitati_partecipanti_matrix = $partecipanti_matrix->Matrix['rows']['sequential'];
        $invitati_aggiudicatario_matrix = $aggiudicatario_matrix->Matrix['rows']['sequential'];

        //---------------------------------------------------------------------------
        //invitati
        foreach ( $invitati_matrix_sequential as $invitato )
        {
            $this->getDataFromMatrix($anagrafiche, $invitato, self::$INVIATO);
        }
        //---------------------------------------------------------------------------
        //partecipanti
        foreach ( $invitati_partecipanti_matrix as $partecipante )
        {
            $this->getDataFromMatrix($anagrafiche, $partecipante, self::$PARTECIPANTE);
        }

        //---------------------------------------------------------------------------
        //aggiudicatari
        foreach ( $invitati_aggiudicatario_matrix as $aggiudicatario )
        {
            $this->getDataFromMatrix($anagrafiche, $aggiudicatario, self::$AGGIUDICATARIO);
        }

        //DUPLICAZIONE RIGHE
        //creo tante righe quanto sono le anagrafiche

        foreach ( $anagrafiche  as $key=>$anagrafica ){
            ksort($anagrafica);
            $values[] = array_merge($row, $anagrafica);
        }

        //gestione eventuali errori
        $this->manageErrors($values, $object);

        $object->resetDataMap();
        eZContentObject::clearCache($object->attribute( 'id' ));
        unset( $data_map );

        return $values;
    }

    private function getDataFromMatrix(&$anagrafiche, $row, $type)
    {

        $columns = $row['columns'];

        //11
        //CF_AZIENDA

        //CF
        if ( !(!isset($columns[0]) || trim($columns[0])===''))
        {
            $cf = $columns[0];

            //FIXME: questo codice è sbagliato
            $result_a = preg_match("/[A-Za-z]{6}[0-9]{2}[A-Za-z]{1}[0-9]{2}[A-Za-z]{1}[0-9A-Za-z]{3}[A-Za-z]{1}/", $cf);
            $result_b = preg_match("/[A-Za-z]{6}[0-9LMNPQRSTUV]{2}[A-Za-z]{1}[0-9LMNPQRSTUV]{2}[A-Za-z]{1}[0-9LMNPQRSTUV]{3}[A-Za-z]{1}/", $cf);
            $result_c = preg_match("/[0-9]{11,11}/", $cf);

            //controllo se rispetta almeno uno dei pattern accettati da avcp
            if (!$result_a && !$result_c && !$result_b)
            {
                $cf = self::$ERRORS_IN_FIELD;
            }

        }

        //CF estero
        if ( !(!isset($columns[1]) || trim($columns[1])===''))
        {
            $cf = $columns[1];
        }

        //almeno uno dei due cf (estero o italiano) ci deve essere, altrimenti errore
        if ( !isset($cf) || trim($cf)==='')
        {
            $cf = self::$ERRORS_IN_FIELD;
        }

        $cf = trim( $cf );

        $anagrafiche_inner = array();

        //controllo se l'anagrafica è già presente come partecipante, destinatario o aggiudicatario
        if(array_key_exists($cf, $anagrafiche)){

           //se è già presente uso quella
           $anagrafiche_inner = $anagrafiche[$cf];
        }else{
            //se non è presente imposto fin da ora tutti i flag a no, poi in base al fatto che sia o meno partecipante, destinatario o aggiudicatario, imposterò a sì i campi giusti nel metodo completeWithType
            $anagrafiche_inner[3] = 'N';
            $anagrafiche_inner[4] = 'N';
            $anagrafiche_inner[5] = 'N';
        }

        $anagrafiche_inner[0] = $cf;

        //12
        //ID_GRUPPO (valorizzato solo se ci sono più soggetti)
        $id_gruppo = '';
        if ( $columns[3] )
        {
            $id_gruppo = $columns[3];
        }
        $anagrafiche_inner[1] = $id_gruppo;

        //13
        //ruolo (TIPO_PARTECIPAZIONE)
        $tipo_partecipazione = '';

        $id_gruppo = trim($id_gruppo);

        //se ci sono più soggetti il tipo partecipazione è obbligatorio
        if($id_gruppo!=''){
            $tipo_partecipazione = self::$ERRORS_IN_FIELD;
        }

        if ( $columns[4] )
        {
            $tipo_partecipazione = preg_replace( '/[^0-9]/', '', $columns[4]);
        }

        //lunghezza massima 1 carattere
        if($id_gruppo!='' && (!$tipo_partecipazione || strlen($tipo_partecipazione)!=1)){
            $tipo_partecipazione = self::$ERRORS_IN_FIELD;
        }
        $anagrafiche_inner[2] = $tipo_partecipazione;

        //14-15-16
        //completo con il tipo di partecipante
        $this->completeWithType($anagrafiche_inner, $type);

        $anagrafiche[$cf] = $anagrafiche_inner;
    }

    private function completeWithType(&$anagrafiche, $type)
    {

        //ATTRIBUTO_AGGIUDICATARIA
        if ( $type == self::$INVIATO )
        {
            $anagrafiche[3] = 'S';
        }

        if ( $type == self::$PARTECIPANTE )
        {
            $anagrafiche[4] = 'S';
        }

        if ( $type == self::$AGGIUDICATARIO )
        {
            $anagrafiche[5] = 'S';
        }
    }


    function createValues()
    {
        $count = $this->fetchCount();

        if ( $count > 0 )
        {
            $length = 50;
            $this->fetchParameters['Offset'] = 0;
            $this->fetchParameters['Limit'] = $length;

            do
            {
                $items = $this->fetch();

                foreach ( $items as $item )
                {
                    //FIX: array_merge usa troppa memoria
                    //$this->values = array_merge($this->values, $this->transformNode( $item ));

                    foreach ( $this->transformNode( $item ) as $value ){
                        $this->values[]= $value;
                    }

                    unset($item);
                }

                $this->fetchParameters['Offset'] += $length;

            } while ( count( $items ) == $length );

            return $this->errors;
        }
    }


    function handleDownload()
    {

        $filename = $this->filename . '.csv';

        header( 'X-Powered-By: eZ Publish' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( "Content-Disposition: attachment; filename=$filename" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );

        $output = fopen('php://output', 'w');
        $runOnce = false;

        foreach ( $this->values as $value )
        {

            if ( !$runOnce )
            {
                fputcsv(
                    $output,
                    array_values( $this->CSVheaders ),
                    $this->options['CSVDelimiter'],
                    $this->options['CSVEnclosure']
                );
                $runOnce = true;
            }
            fputcsv(
                $output,
                $value,
                $this->options['CSVDelimiter'],
                $this->options['CSVEnclosure']
            );
            flush();
        }

    }


    private function manageErrors($values, $object){

        $object_id = $object->ID;

        foreach ( $values as $value ){

            //recupero le posizioni degli errori
            $errors_positions = array_keys($value, self::$ERRORS_IN_FIELD);

            foreach ( $errors_positions as $errors_position )
            {
                //prendo l'identificatore dell'errore
                $error_identifier = $this->CSVheaders[$errors_position];
                $error_description = $this->errorDescriptor[$error_identifier];
                $errors = array();

                if (array_key_exists($object_id, $this->errors)) {
                    $errors = $this->errors[$object_id];
                }

                array_push($errors, $error_description);
                $errors = array_unique($errors);
                $this->errors[$object_id] = $errors;
            }
        }
    }

    private function createErrorDescriptor(){

        $this->errorDescriptor = array('CIG' => "CIG è un campo obbligatorio. Va sempre indicato oppure sostituito con l'identificativo PAT assegnato da SICOPAT, oppure, se si tratta di primo inserimento di un contratto senza CIG, inserire un identificativo di 10 caratteri che inizi con 9 (ES 9000000001, 9000000002, ecc. ) e che risulti univoco all’interno del file. L'associazione con l'identificativo assegnato dal sistema SICOPAT verrà specificata nell'esito del caricamento.",
                                       'FLAG_CONTRATTO_SENZA_CIG' => "",
                                       'ANNO_PUBBLICAZIONE' => 'Anno di pubblicazione è un campo obbligatorio. Sono ammessi 4 caratteri numerici.',
                                       'OGGETTO' => "Oggetto: Lunghezza massima consentita 500 caratteri.",
                                       'SCELTA_CONTRAENTE' => "Scelta contraente è un campo obbligatorio. Sono ammessi 2 caratteri numerici (01 oppure 1 oppure 14, 17, ecc.)",
                                       'IMPORTO_GARA' => "Importo gara è un campo obbligatorio. Sono ammessi solo numeri senza separatori di migliaia e con il punto come separatore di decimali (Max 2 cifre decimali). (es: 1234567.89).",
                                       'IMPORTO_AGGIUDICAZIONE' => "Importo aggiudicazione: Sono ammessi solo numeri senza separatori di migliaia e con il punto come separatore di decimali (Max 2 cifre decimali). (es: 1234567.89).",
                                       'DATA_INIZIO' => "Data inizio: Formato: GG/MM/AAAA.",
                                       'DATA_ULTIMAZIONE' => "Data ultimazione: Formato: GG/MM/AAAA.",
                                       'IMPORTO_SOMME_LIQUIDATE' => "Importo somme liquidate: Sono ammessi solo numeri senza separatori di migliaia e con il punto come separatore di decimali (Max 2 cifre decimali). (es: 1234567.89).",
                                       'FLAG_COMPLETAMENTO' => "",
                                       'CF_AZIENDA' => "Codice fiscale dell'operatore economico o identificativo fiscale estero sono campi obbligatori. Nel caso di operatore economico italiano devono essere rispettati i formati standard",
                                       'ID_GRUPPO' => "",
                                       'TIPO_PARTECIPAZIONE' => "Tipo partecipazione: Sono ammessi 1 carattere numerico tra 1,2,3,4 e 5.",
                                       'ATTRIBUTO_INVITATA' => "",
                                       'ATRIBUTO_PARTECIPANTE' => "",
                                       'ATTRIBUTO_AGGIUDICATARIA' => ""
        );
    }


    private function checkImporti($importo){

        if($importo){
            //solo numeri e massimo 2 decimali
            return preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $importo);
        }

        return true;
    }
}

?>