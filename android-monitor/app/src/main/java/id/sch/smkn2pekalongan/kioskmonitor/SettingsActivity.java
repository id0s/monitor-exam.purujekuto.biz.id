package id.sch.smkn2pekalongan.kioskmonitor;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

public class SettingsActivity extends AppCompatActivity {

    private EditText etServerUrl;
    private Button btnSave;
    private SharedPreferences sharedPreferences;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_settings);

        // Set action bar title and back button
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle(R.string.title_activity_settings);
        }

        etServerUrl = findViewById(R.id.etServerUrl);
        btnSave = findViewById(R.id.btnSave);

        sharedPreferences = getSharedPreferences("kiosk_prefs", MODE_PRIVATE);
        
        // Load saved URL if exists
        String savedUrl = sharedPreferences.getString("server_url", "");
        etServerUrl.setText(savedUrl);

        btnSave.setOnClickListener(v -> {
            String inputUrl = etServerUrl.getText().toString().trim();
            if (inputUrl.isEmpty()) {
                Toast.makeText(this, R.string.error_empty_url, Toast.LENGTH_SHORT).show();
                return;
            }

            // Auto-append http:// if no schema present
            if (!inputUrl.startsWith("http://") && !inputUrl.startsWith("https://")) {
                inputUrl = "http://" + inputUrl;
            }

            // Save to shared preferences
            SharedPreferences.Editor editor = sharedPreferences.edit();
            editor.putString("server_url", inputUrl);
            editor.apply();

            Toast.makeText(this, R.string.success_save, Toast.LENGTH_SHORT).show();
            
            // Set result and finish
            setResult(RESULT_OK);
            finish();
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
}
