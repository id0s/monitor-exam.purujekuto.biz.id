package id.sch.smkn2pekalongan.kioskmonitor;

import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.InputType;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.NetworkInterface;
import java.net.URL;
import java.util.Collections;
import java.util.List;

public class MainActivity extends AppCompatActivity {

    private static final int REQUEST_SETTINGS = 100;
    private static final int HEARTBEAT_INTERVAL_MS = 10000; // 10 detik

    private WebView webView;
    private SwipeRefreshLayout swipeRefresh;
    private LinearLayout errorLayout;
    private TextView errorText;
    private Button btnRetry;
    
    private String serverUrl = "";
    private boolean isError = false;
    private boolean isExamActive = false;
    private String currentExamUrl = "";
    private boolean isAppInForeground = true;

    private Handler heartbeatHandler = new Handler(Looper.getMainLooper());
    private Runnable heartbeatRunnable = new Runnable() {
        @Override
        public void run() {
            if (isAppInForeground) {
                checkExamStatusFromServer();
            }
            heartbeatHandler.postDelayed(this, HEARTBEAT_INTERVAL_MS);
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Setup custom Toolbar
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        webView = findViewById(R.id.webView);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        errorLayout = findViewById(R.id.errorLayout);
        errorText = findViewById(R.id.errorText);
        btnRetry = findViewById(R.id.btnRetry);

        setupWebView();

        // Swipe down to refresh
        swipeRefresh.setOnRefreshListener(() -> {
            if (!serverUrl.isEmpty()) {
                if (isExamActive && !currentExamUrl.isEmpty()) {
                    webView.reload();
                } else {
                    checkExamStatusFromServer();
                }
            } else {
                swipeRefresh.setRefreshing(false);
                checkServerUrl();
            }
        });

        // Click retry button
        btnRetry.setOnClickListener(v -> {
            errorLayout.setVisibility(View.GONE);
            webView.setVisibility(View.VISIBLE);
            isError = false;
            
            if (serverUrl.isEmpty()) {
                checkServerUrl();
            } else {
                checkExamStatusFromServer();
            }
        });

        checkServerUrl();
    }

    @Override
    protected void onResume() {
        super.onResume();
        isAppInForeground = true;
        
        // Mulai polling server
        heartbeatHandler.removeCallbacks(heartbeatRunnable);
        heartbeatHandler.post(heartbeatRunnable);
        
        // Laporkan kembali status aktif jika ujian sedang berjalan
        sendFocusStatus(isExamActive ? 1 : 0);
    }

    @Override
    protected void onPause() {
        super.onPause();
        isAppInForeground = false;
        
        // Laporkan focus loss (status 2 = locked/cheating attempt) ke server
        sendFocusStatus(2);
        
        // Stop polling saat di background untuk menghemat baterai/data
        heartbeatHandler.removeCallbacks(heartbeatRunnable);
    }

    private void checkServerUrl() {
        SharedPreferences prefs = getSharedPreferences("kiosk_prefs", MODE_PRIVATE);
        serverUrl = prefs.getString("server_url", "");

        if (serverUrl.isEmpty()) {
            // Jika belum ada konfigurasi IP, arahkan langsung ke Settings
            Intent intent = new Intent(this, SettingsActivity.class);
            startActivityForResult(intent, REQUEST_SETTINGS);
        } else {
            // Mulai lakukan verifikasi ke server lokal
            isError = false;
            errorLayout.setVisibility(View.GONE);
            webView.setVisibility(View.VISIBLE);
            checkExamStatusFromServer();
        }
    }

    private void setupWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setSupportZoom(true);
        settings.setBuiltInZoomControls(true);
        settings.setDisplayZoomControls(false);
        
        // Optimasi Cache
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        
        // Tambahkan CUSTOM USER-AGENT agar server ujian (student.smk2pekalongan.sch.id)
        // bisa memverifikasi bahwa siswa benar-benar membuka dari aplikasi resmi ini.
        String defaultUA = settings.getUserAgentString();
        settings.setUserAgentString(defaultUA + " SMK2PekalonganExamBrowser/1.0");
        
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                super.onPageStarted(view, url, favicon);
                isError = false;
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                swipeRefresh.setRefreshing(false);
                if (!isError && isExamActive) {
                    errorLayout.setVisibility(View.GONE);
                    webView.setVisibility(View.VISIBLE);
                }
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                super.onReceivedError(view, request, error);
                if (request.isForMainFrame()) {
                    showError(error.getDescription().toString());
                }
            }

            @SuppressWarnings("deprecation")
            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                super.onReceivedError(view, errorCode, description, failingUrl);
                showError(description);
            }
        });
    }

    /**
     * Memeriksa status ujian secara berkala ke server lokal.
     */
    private void checkExamStatusFromServer() {
        if (serverUrl.isEmpty()) return;

        new Thread(() -> {
            String ip = getLocalIpAddress();
            String deviceModel = "Android-" + Build.MODEL;
            String checkUrl = serverUrl + "/api/check-jadwal?pc_name=" + Uri.encode(deviceModel) 
                    + "&ip=" + Uri.encode(ip) + "&mac=" + Uri.encode("02:00:00:00:00:00")
                    + "&chrome_running=" + (isExamActive ? "1" : "0");

            HttpURLConnection conn = null;
            try {
                URL url = new URL(checkUrl);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setConnectTimeout(5000);
                conn.setReadTimeout(5000);
                conn.setRequestProperty("User-Agent", "SMK2PekalonganExamBrowser/1.0");

                int responseCode = conn.getResponseCode();
                if (responseCode == HttpURLConnection.HTTP_OK) {
                    BufferedReader in = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = in.readLine()) != null) {
                        response.append(line);
                    }
                    in.close();

                    JSONObject json = new JSONObject(response.toString());
                    String status = json.optString("status", "");
                    String event = json.optString("event", "");
                    String examUrl = json.optString("url", "");

                    runOnUiThread(() -> {
                        swipeRefresh.setRefreshing(false);
                        if ("success".equals(status)) {
                            if (!"NO_EVENT".equals(event) && !examUrl.isEmpty()) {
                                handleExamActive(event, examUrl);
                            } else {
                                handleExamInactive();
                            }
                        } else {
                            showError("Respon server tidak valid.");
                        }
                    });
                } else {
                    runOnUiThread(() -> {
                        swipeRefresh.setRefreshing(false);
                        showError("Server merespon dengan kode: " + responseCode);
                    });
                }
            } catch (Exception e) {
                e.printStackTrace();
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    showError("Tidak dapat terhubung ke server lokal. Pastikan Wi-Fi terhubung.");
                });
            } finally {
                if (conn != null) conn.disconnect();
            }
        }).start();
    }

    /**
     * Mengirimkan notifikasi focus loss / focus restore ke server.
     */
    private void sendFocusStatus(int statusValue) {
        if (serverUrl.isEmpty()) return;

        new Thread(() -> {
            String ip = getLocalIpAddress();
            String deviceModel = "Android-" + Build.MODEL;
            String checkUrl = serverUrl + "/api/check-jadwal?pc_name=" + Uri.encode(deviceModel) 
                    + "&ip=" + Uri.encode(ip) + "&mac=" + Uri.encode("02:00:00:00:00:00")
                    + "&chrome_running=" + statusValue;
            try {
                URL url = new URL(checkUrl);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setConnectTimeout(3000);
                conn.setReadTimeout(3000);
                conn.setRequestProperty("User-Agent", "SMK2PekalonganExamBrowser/1.0");
                conn.getResponseCode(); // Execute request
                conn.disconnect();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    private void handleExamActive(String eventName, String examUrl) {
        isExamActive = true;
        
        // Sembunyikan toolbar (kiosk mode) agar layar penuh untuk ujian
        if (getSupportActionBar() != null) {
            getSupportActionBar().hide();
        }

        if (!currentExamUrl.equals(examUrl)) {
            currentExamUrl = examUrl;
            webView.setVisibility(View.VISIBLE);
            errorLayout.setVisibility(View.GONE);
            webView.loadUrl(examUrl);
        }
    }

    private void handleExamInactive() {
        isExamActive = false;
        currentExamUrl = "";

        // Tampilkan kembali toolbar
        if (getSupportActionBar() != null) {
            getSupportActionBar().show();
            getSupportActionBar().setTitle("Ujian Belum Aktif");
            getSupportActionBar().setSubtitle("Menunggu proktor memulai ujian...");
        }

        webView.loadUrl("about:blank");
        webView.setVisibility(View.GONE);
        
        errorLayout.setVisibility(View.VISIBLE);
        errorText.setText("Menunggu jadwal ujian diaktifkan oleh proktor di server lokal...");
        btnRetry.setText("Refresh Manual");
    }

    private void showError(String description) {
        // Hanya tampilkan layar error jika tidak sedang dalam mode ujian (agar tidak mengganggu WebView yang sudah termuat)
        if (!isExamActive) {
            isError = true;
            webView.setVisibility(View.GONE);
            errorLayout.setVisibility(View.VISIBLE);
            if (description != null && !description.trim().isEmpty()) {
                errorText.setText(getString(R.string.error_connection) + "\n\nDetail: " + description);
            } else {
                errorText.setText(R.string.error_connection);
            }
            btnRetry.setText("Coba Lagi");
        } else {
            Toast.makeText(this, "Koneksi terganggu: " + description, Toast.LENGTH_SHORT).show();
        }
    }

    /**
     * Meminta password pengawas sebelum menjalankan aksi tertentu (Settings / Exit).
     */
    private void showProctorPasswordDialog(Runnable onSuccess) {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        builder.setTitle("Password Pengawas");
        builder.setMessage("Masukkan password pengawas untuk melanjutkan:");

        final EditText input = new EditText(this);
        input.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD);
        builder.setView(input);

        builder.setPositiveButton("OK", (dialog, which) -> {
            String password = input.getText().toString();
            if ("pekalongan2".equals(password)) {
                onSuccess.run();
            } else {
                Toast.makeText(MainActivity.this, "Password salah!", Toast.LENGTH_SHORT).show();
            }
        });
        builder.setNegativeButton("Batal", (dialog, which) -> dialog.cancel());
        builder.show();
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.main_menu, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        int id = item.getItemId();
        if (id == R.id.action_settings) {
            // Minta password pengawas sebelum masuk menu pengaturan
            showProctorPasswordDialog(() -> {
                Intent intent = new Intent(MainActivity.this, SettingsActivity.class);
                startActivityForResult(intent, REQUEST_SETTINGS);
            });
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == REQUEST_SETTINGS) {
            checkServerUrl();
        }
    }

    @Override
    public void onBackPressed() {
        if (isExamActive) {
            // Minta password pengawas untuk keluar dari ujian
            showProctorPasswordDialog(() -> {
                isExamActive = false;
                currentExamUrl = "";
                webView.loadUrl("about:blank");
                MainActivity.super.onBackPressed();
            });
        } else {
            if (webView.canGoBack() && !isError) {
                webView.goBack();
            } else {
                super.onBackPressed();
            }
        }
    }

    /**
     * Mendapatkan IP Address lokal perangkat
     */
    private String getLocalIpAddress() {
        try {
            List<NetworkInterface> interfaces = Collections.list(NetworkInterface.getNetworkInterfaces());
            for (NetworkInterface intf : interfaces) {
                List<java.net.InetAddress> addrs = Collections.list(intf.getInetAddresses());
                for (java.net.InetAddress addr : addrs) {
                    if (!addr.isLoopbackAddress()) {
                        String sAddr = addr.getHostAddress();
                        boolean isIPv4 = sAddr.indexOf(':') < 0;
                        if (isIPv4) {
                            return sAddr;
                        }
                    }
                }
            }
        } catch (Exception ex) {
            ex.printStackTrace();
        }
        return "127.0.0.1";
    }
}

