<?php

class WordpressFacade
{
    private static $_instance = null;

    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use WordpressFacade::getInstance';
            throw new Exception($message);
         }
    } // end __construct
    
    
    public function getAttachmentsByPostID($postParent, $fileType)
    {
        $attachmentQuery = array(
            'numberposts' => -1,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_parent' => $postParent,
            'post_mime_type' => $fileType
        );
        
        $attachments = get_posts($attachmentQuery);
        
        return $attachments;
    } // end getAttachmentsByPostID
    
    public function getAbsolutePath($url)
    {
        return str_replace(home_url('/'), ABSPATH, $url);
    } // end getAbsolutePath
    
    public function addAttachment($idPost, $attachment, $path)
    {
        $id = wp_insert_attachment($attachment, $path, $idPost);
        
        return $id;
    } // end addAttachment
    
    public function getPluginData($pluginPath)
    {
        return get_plugin_data($pluginPath);    
    } // end getPluginData

    public static function createQueryInstance($params)
    {
        return new WP_Query($params);
    } // end createQueryInstance
    
    public function onRemoveAllActions($hookName)
    {
        return remove_all_actions($hookName);
    } // end onRemoveAllActions
}
