import 'package:flutter/material.dart';
import 'package:login_farmer/models/booking_model.dart';
import 'package:login_farmer/pages/booking_detail.dart';
import 'package:login_farmer/service/api_service.dart';
import 'package:login_farmer/service/auth_service.dart';

class TractorCategoriesPage extends StatefulWidget {
  const TractorCategoriesPage({Key? key}) : super(key: key);

  @override
  State<TractorCategoriesPage> createState() => _TractorCategoriesPageState();
}

class _TractorCategoriesPageState extends State<TractorCategoriesPage> {
  late ApiService _apiService;
  List<TractorModel> tractors = [];
  bool isLoading = true;
  String? errorMessage;

  @override
  void initState() {
    super.initState();
    // Initialize API service
    final authService = AuthService();
    _apiService = ApiService(authService: authService);

    // Fetch tractors when the screen loads
    _fetchTractors();
  }

  Future<void> _fetchTractors() async {
    try {
      setState(() {
        isLoading = true;
        errorMessage = null;
      });

      final result = await _apiService.getTractors();

      if (result['success'] == true) {
        // Convert API data to tractors list
        final List<dynamic> tractorData = result['data']['data'];
        final List<TractorModel> fetchedTractors =
            tractorData.map((item) => TractorModel.fromJson(item)).toList();

        setState(() {
          tractors = fetchedTractors;
          isLoading = false;
        });
      } else {
        setState(() {
          errorMessage = result['message'] ?? 'Failed to load tractors';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        errorMessage = 'Error: $e';
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Tractor Categories',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF375534),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchTractors,
            color: Colors.white,
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        errorMessage!,
                        style: TextStyle(color: Colors.red[700]),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchTractors,
                        child: const Text('Try Again'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF375534),
                          foregroundColor: Colors.white,
                        ),
                      ),
                    ],
                  ),
                )
              : tractors.isEmpty
                  ? const Center(
                      child: Text('No tractors available at the moment'),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: tractors.length,
                      itemBuilder: (context, index) {
                        final tractor = tractors[index];
                        return _buildTractorCard(tractor);
                      },
                    ),
    );
  }

  Widget _buildTractorCard(TractorModel tractor) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 3,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => BookingDetailsPage(
                selectedTractor: tractor,
              ),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(12),
              ),
              child: tractor.imageUrl.startsWith('http')
                  ? Image.network(
                      tractor.imageUrl,
                      height: 180,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return _buildPlaceholderImage();
                      },
                    )
                  : Image.asset(
                      tractor.imageUrl,
                      height: 180,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return _buildPlaceholderImage();
                      },
                    ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          tractor.name,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFF375534).withOpacity(0.2),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          '\$${tractor.pricePerAcre.toStringAsFixed(2)}/acre',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF375534),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text('Brand: ${tractor.brand}'),
                  Text('Horsepower: ${tractor.horsePower} HP'),
                  const SizedBox(height: 8),
                  Text(
                    tractor.description,
                    style: TextStyle(color: Colors.grey[700]),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      height: 180,
      color: Colors.grey[300],
      child: const Center(
        child: Icon(
          Icons.agriculture,
          size: 80,
          color: Colors.grey,
        ),
      ),
    );
  }
}
