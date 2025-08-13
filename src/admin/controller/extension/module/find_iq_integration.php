<?php

class ControllerExtensionModuleFindIqIntegration extends Controller
{

    private $error = array();

    private $event_code = 'find_iq_script_connect';


    private $module_path = 'extension/module/find_iq_integration';

    public function index()
    {

        $this->load->language($this->module_path);
        $this->document->setTitle($this->language->get('heading_name'));

        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_setting_setting->editSetting('module_find_iq_integration_status', array('module_find_iq_integration_status' => $this->request->post['status']));
            unset($this->request->post['status']);

            $this->model_setting_setting->editSetting('module_find_iq_integration', array('module_find_iq_integration_config' => $this->request->post));

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], true));
        }


        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_name'),
            'href' => $this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['download'] = $this->url->link($this->module_path . '/download', 'user_token=' . $this->session->data['user_token'], true);
        $data['clear'] = $this->url->link($this->module_path . '/clear', 'user_token=' . $this->session->data['user_token'], true);

        $data['log'] = '';

        $file = DIR_LOGS . "find_iq_integration_cron.log";

        if (file_exists($file)) {
            $size = filesize($file);

            if ($size >= 5242880) {
                $suffix = array(
                    'B',
                    'KB',
                    'MB',
                    'GB',
                    'TB',
                    'PB',
                    'EB',
                    'ZB',
                    'YB'
                );

                $i = 0;

                while (($size / 1024) > 1) {
                    $size = $size / 1024;
                    $i++;
                }

                $data['error_warning'] = sprintf($this->language->get('error_warning'), basename($file), round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i]);
            } else {
                $data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
            }
        }


        $data['status'] = $this->config->get('module_find_iq_integration_status');
        $data['config'] = $this->config->get('module_find_iq_integration_config');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->module_path, $data));
    }


    public function install()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent($this->event_code, 'catalog/view/*/before', 'tool/find_iq/addScript');

        $this->load->model('extension/module/find_iq_integration');
        $this->model_extension_module_find_iq_integration->install();
    }

    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this->event_code);


        $this->load->model('extension/module/find_iq_integration');
        $this->model_extension_module_find_iq_integration->uninstall();
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', $this->module_path)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }


    public function download()
    {
        if (file_exists($this->file) && filesize($this->file) > 0) {
            $this->response->addheader('Pragma: public');
            $this->response->addheader('Expires: 0');
            $this->response->addheader('Content-Description: File Transfer');
            $this->response->addheader('Content-Type: application/octet-stream');
            $this->response->addheader('Content-Disposition: attachment; filename="' . 'find_iq_' . date('Y-m-d_H-i-s', time()) . '.log"');
            $this->response->addheader('Content-Transfer-Encoding: binary');

            $this->response->setOutput(file_get_contents($this->file, FILE_USE_INCLUDE_PATH, null));
        } else {
            $this->session->data['error'] = sprintf($this->language->get('error_small_log_warning'), basename($this->file), '0B');

            $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function clear()
    {

        if (!$this->user->hasPermission('modify', $this->module_path)) {
            $this->session->data['error'] = $this->language->get('error_permission');
        } else {
            $handle = fopen($this->file, 'w+');

            fclose($handle);

            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->response->redirect($this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], true));
    }
}